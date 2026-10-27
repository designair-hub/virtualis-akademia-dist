<?php

namespace VirtualisAkademia\Frontend;

use VirtualisAkademia\Hookable;
use VirtualisAkademia\Support\HomePage;
use VirtualisAkademia\Support\MetaversePage;

/**
 * The plugin owns the page template: wherever the site would fall back to
 * the active (block) theme's template -- 404, search, archives, the blog
 * index, posts, ordinary pages, any other post type -- it renders
 * templates/shell.php instead, in the product's own look (brand bar, dark
 * ground, partner footer). So the default theme never shows.
 *
 * Left alone, because they bring their own complete layout (and already hide
 * the theme's header/footer): the landing / register / metaverse pages
 * (shortcodes), Ultimate Member's core pages, the privacy page and every
 * LearnDash course / lesson / topic / quiz (LearnDash's focus mode).
 */
class BrandedShell implements Hookable {

	private static ?bool $active = null;

	public function register(): void {
		add_filter( 'template_include', [ $this, 'template' ], 99 );
		add_filter( 'body_class', [ $this, 'body_class' ] );
	}

	public function template( $template ) {
		return self::active() ? VA_PLUGIN_DIR . '/templates/shell.php' : $template;
	}

	public function body_class( array $classes ): array {
		if ( self::active() ) {
			$classes[] = 'va-shell-page';
		}

		return $classes;
	}

	/** Whether this request is rendered by the plugin's own shell. */
	public static function active(): bool {
		if ( null !== self::$active ) {
			return self::$active;
		}

		if ( is_admin() || is_feed() || is_embed() || is_robots() || is_trackback() || ! did_action( 'wp' ) ) {
			return false; // Not cached: too early / not a page request.
		}

		self::$active = self::decide();

		return self::$active;
	}

	private static function decide(): bool {
		if ( is_404() || is_search() || is_archive() || is_attachment() ) {
			return true;
		}

		if ( is_home() ) {
			return ! is_front_page() || ! self::has_own_layout( (int) get_queried_object_id() );
		}

		if ( is_singular() ) {
			$post = get_queried_object();

			if ( ! $post instanceof \WP_Post ) {
				return true;
			}

			if ( str_starts_with( $post->post_type, 'sfwd-' ) || 'groups' === $post->post_type ) {
				return false; // LearnDash.
			}

			return ! self::has_own_layout( $post->ID );
		}

		return true;
	}

	private static function has_own_layout( int $post_id ): bool {
		if ( ! $post_id ) {
			return false;
		}

		foreach ( [ HomePage::get_id(), \VirtualisAkademia\Support\RegisterPage::get_id(), MetaversePage::get_id(), (int) get_option( 'wp_page_for_privacy_policy' ) ] as $own ) {
			if ( $own && (int) $own === $post_id ) {
				return true;
			}
		}

		return '' !== (string) get_post_meta( $post_id, '_um_core', true );
	}

	/** Where "back" leads: the product itself for members, the landing page otherwise. */
	public static function home_link(): array {
		if ( is_user_logged_in() && MetaversePage::get_url() ) {
			return [ 'url' => (string) MetaversePage::get_url(), 'label' => __( 'Vissza a Virtuális Akadémiába', 'virtualis-akademia' ) ];
		}

		return [ 'url' => (string) ( HomePage::get_url() ?: home_url( '/' ) ), 'label' => __( 'Vissza a kezdőlapra', 'virtualis-akademia' ) ];
	}
}
