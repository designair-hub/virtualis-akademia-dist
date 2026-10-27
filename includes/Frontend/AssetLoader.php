<?php

namespace VirtualisAkademia\Frontend;

use VirtualisAkademia\Hookable;

class AssetLoader implements Hookable {

	/**
	 * Our Vite bundles are ES-module output. Loaded as classic scripts, their
	 * minified top-level declarations (`function _(…)`, `const A=…`) became
	 * window globals -- clobbering WordPress's own `_` (Underscore/Lodash:
	 * "_.memoize is not a function") and letting later scripts overwrite our
	 * functions before DOMContentLoaded. As `type="module"` they're scoped.
	 */
	private const MODULE_HANDLES = [ 'va-app', 'va-lesson', 'va-register', 'va-account' ];

	public function register(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue' ] );
		add_filter( 'script_loader_tag', [ $this, 'as_module' ], 10, 2 );
	}

	public function as_module( string $tag, string $handle ): string {
		if ( ! in_array( $handle, self::MODULE_HANDLES, true ) || str_contains( $tag, 'type="module"' ) ) {
			return $tag;
		}

		// Only the external bundle's own tag -- inline "before" data scripts
		// (window.vaSceneData, …) carry an `-before` id and stay classic.
		return preg_replace( '#<script(?![^>]*-js-(?:before|after))([^>]*\ssrc=)#', '<script type="module"$1', $tag, 1 );
	}

	public function maybe_enqueue(): void {
		// The plugin's own page shell (404, search, archives, plain pages).
		if ( BrandedShell::active() ) {
			$this->enqueue_fonts();
			$this->enqueue_app_style( [] );

			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		// Every LearnDash singular view -- course, lesson, topic, quiz -- pulls
		// the same bundle: the design-system re-skin (assets/src/styles/learndash.scss)
		// plus the lesson/topic quiz-callout hydration (harmless no-op on
		// course/quiz pages, which carry no [data-va-quiz] markup).
		if ( is_singular( [ 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ] ) ) {
			$this->enqueue_fonts();
			$this->enqueue_lesson_script();

			return;
		}

		$content = get_post()->post_content ?? '';

		if ( has_shortcode( $content, 'va_landing' ) || has_shortcode( $content, 'va_register' ) ) {
			$this->enqueue_fonts();
			$this->enqueue_app_style( [] );

			if ( has_shortcode( $content, 'va_register' ) ) {
				$this->enqueue_register_script();
			}

			return;
		}

		$privacy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );

		if ( $privacy_page_id && is_page( $privacy_page_id ) ) {
			$this->enqueue_fonts();
			$this->enqueue_app_style( [] );

			return;
		}

		if ( UmCorePages::current_core_slug() ) {
			$this->enqueue_fonts();
			$this->enqueue_app_style( [] );

			return;
		}

		if ( is_user_logged_in() && 'account' === get_post_meta( get_the_ID(), '_um_core', true ) ) {
			$this->enqueue_fonts();
			$this->enqueue_app_style( [] );
			$this->enqueue_account_script();

			return;
		}

		if ( ! has_shortcode( $content, 'va_metaverse' ) || ! is_user_logged_in() ) {
			return;
		}

		$this->enqueue_fonts();

		wp_enqueue_style(
			'va-pannellum',
			VA_PLUGIN_URL . 'assets/vendor/pannellum/pannellum.css',
			[],
			VA_VERSION
		);

		wp_enqueue_script(
			'va-pannellum',
			VA_PLUGIN_URL . 'assets/vendor/pannellum/pannellum.js',
			[],
			VA_VERSION,
			true
		);

		$script_src = $this->enqueue_app_style( [ 'va-pannellum' ] );

		wp_enqueue_script( 'va-app', $script_src, [ 'va-pannellum' ], VA_VERSION, true );

		$privacy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );

		$scene_data                     = ( new SceneDataProvider() )->get_scene_data();
		$scene_data['restUrl']          = esc_url_raw( rest_url( 'virtualis-akademia/v1' ) );
		$scene_data['restNonce']        = wp_create_nonce( 'wp_rest' );
		$scene_data['privacyPolicyUrl'] = $privacy_page_id ? get_permalink( $privacy_page_id ) : '';
		$scene_data['logoutUrl']        = wp_logout_url();
		$scene_data['partners']         = \VirtualisAkademia\Support\Partners::all();
		$scene_data['me']               = \VirtualisAkademia\Profile\ProfileCard::identity( get_current_user_id() );

		wp_add_inline_script(
			'va-app',
			'window.vaSceneData = ' . wp_json_encode( $scene_data ) . ';',
			'before'
		);
	}

	/**
	 * The LearnDash bundle: the LD30 design-system re-skin
	 * (assets/src/styles/learndash.scss) plus the interactive task/quiz
	 * callout hydration embedded in lesson/topic content (assets/src/lesson.js).
	 * A separate, much smaller Vite entry than the full metaverse app bundle --
	 * these pages don't touch Pannellum, the HUD, or any of the SPA widgets.
	 */
	private function enqueue_lesson_script(): void {
		$entry_js      = 'assets/src/lesson.js';
		$script_src    = VA_PLUGIN_URL . 'assets/dist/lesson.js';
		$style_src     = null;
		$manifest_path = VA_PLUGIN_DIR . '/assets/dist/.vite/manifest.json';

		if ( ! file_exists( $manifest_path ) ) {
			$manifest_path = VA_PLUGIN_DIR . '/assets/dist/manifest.json';
		}

		if ( file_exists( $manifest_path ) ) {
			$manifest = json_decode( (string) file_get_contents( $manifest_path ), true );

			if ( isset( $manifest[ $entry_js ]['file'] ) ) {
				$script_src = VA_PLUGIN_URL . 'assets/dist/' . $manifest[ $entry_js ]['file'];
			}

			if ( ! empty( $manifest[ $entry_js ]['css'][0] ) ) {
				$style_src = VA_PLUGIN_URL . 'assets/dist/' . $manifest[ $entry_js ]['css'][0];
			}
		}

		if ( $style_src ) {
			wp_enqueue_style( 'va-lesson', $style_src, [], VA_VERSION );
		}

		wp_enqueue_script( 'va-lesson', $script_src, [], VA_VERSION, true );
	}

	/**
	 * The registration page's small rules script (exclusive options + the
	 * "Egyéb" reveal), fed the option wording from ParticipationFields so
	 * PHP stays the single source of truth.
	 */
	private function enqueue_register_script(): void {
		$entry_js      = 'assets/src/register.js';
		$script_src    = VA_PLUGIN_URL . 'assets/dist/register.js';
		$manifest_path = VA_PLUGIN_DIR . '/assets/dist/.vite/manifest.json';

		if ( file_exists( $manifest_path ) ) {
			$manifest = json_decode( (string) file_get_contents( $manifest_path ), true );

			if ( isset( $manifest[ $entry_js ]['file'] ) ) {
				$script_src = VA_PLUGIN_URL . 'assets/dist/' . $manifest[ $entry_js ]['file'];
			}
		}

		wp_enqueue_script( 'va-register', $script_src, [], VA_VERSION, true );
		wp_add_inline_script(
			'va-register',
			'window.vaRegisterFields = ' . wp_json_encode( [
				'statusKey'   => \VirtualisAkademia\Support\ParticipationFields::STATUS_KEY,
				'otherKey'    => \VirtualisAkademia\Support\ParticipationFields::OTHER_KEY,
				'otherOption' => \VirtualisAkademia\Support\ParticipationFields::OPTION_OTHER,
				'exclusive'   => \VirtualisAkademia\Support\ParticipationFields::EXCLUSIVE,
				'legalPages'  => $this->legal_pages(),
			] ) . ';',
			'before'
		);
	}

	/**
	 * The profile page's ID card + picture upload (assets/src/account.js),
	 * fed the same data as the metaverse's profile popup.
	 */
	private function enqueue_account_script(): void {
		$entry_js      = 'assets/src/account.js';
		$script_src    = VA_PLUGIN_URL . 'assets/dist/account.js';
		$manifest_path = VA_PLUGIN_DIR . '/assets/dist/.vite/manifest.json';

		if ( file_exists( $manifest_path ) ) {
			$manifest = json_decode( (string) file_get_contents( $manifest_path ), true );

			if ( isset( $manifest[ $entry_js ]['file'] ) ) {
				$script_src = VA_PLUGIN_URL . 'assets/dist/' . $manifest[ $entry_js ]['file'];
			}
		}

		wp_enqueue_script( 'va-account', $script_src, [], VA_VERSION, true );
		wp_add_inline_script(
			'va-account',
			'window.vaProfile = ' . wp_json_encode( [
				'card'      => \VirtualisAkademia\Profile\ProfileCard::for_user( get_current_user_id() ),
				'limits'    => \VirtualisAkademia\Profile\Avatar::limits(),
				'restUrl'   => esc_url_raw( rest_url( 'virtualis-akademia/v1' ) ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
			] ) . ';',
			'before'
		);
	}

	/**
	 * The privacy policy and the terms of use, for the registration form's
	 * consent checkboxes: each label's phrase becomes a link that opens the
	 * page's text in a popup (register.js). Content is embedded -- no extra
	 * request when it's opened.
	 *
	 * @return array<int, array{match: string, title: string, url: string, html: string}>
	 */
	private function legal_pages(): array {
		$entries = [
			[ 'match' => 'adatkezelési tájékoztató', 'id' => (int) get_option( 'wp_page_for_privacy_policy' ) ],
			[ 'match' => 'felhasználási feltétel', 'id' => (int) \VirtualisAkademia\Support\TermsPage::get_id() ],
		];
		$pages   = [];

		foreach ( $entries as $entry ) {
			$post = $entry['id'] ? get_post( $entry['id'] ) : null;

			if ( ! $post || 'publish' !== $post->post_status ) {
				continue;
			}

			$pages[] = [
				'match' => $entry['match'],
				'title' => html_entity_decode( get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
				'url'   => (string) get_permalink( $post ),
				// Not in the loop here, so the page-shell content filters stay out of it.
				'html'  => wp_kses_post( apply_filters( 'the_content', $post->post_content ) ),
			];
		}

		return $pages;
	}

	private function enqueue_fonts(): void {
		wp_enqueue_style(
			'va-google-fonts',
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700;800&family=JetBrains+Mono:wght@400;500&display=swap',
			[],
			null
		);
	}

	/**
	 * Both the landing page and the metaverse viewer share the single
	 * compiled app.scss bundle (tokens + every widget's styles) rather than
	 * a second Vite entry -- it's a few extra kilobytes of unused CSS on
	 * whichever page loads it, which is cheaper than a second build target
	 * for what's still a handful of small, related stylesheets.
	 *
	 * Returns the resolved script src so the metaverse path can also
	 * enqueue app.js from the same manifest lookup.
	 */
	private function enqueue_app_style( array $style_deps ): string {
		$entry_js   = 'assets/src/app.js';
		$script_src = VA_PLUGIN_URL . 'assets/dist/app.js';
		$style_src  = null;

		$manifest_path = VA_PLUGIN_DIR . '/assets/dist/.vite/manifest.json';

		if ( ! file_exists( $manifest_path ) ) {
			$manifest_path = VA_PLUGIN_DIR . '/assets/dist/manifest.json';
		}

		if ( file_exists( $manifest_path ) ) {
			$manifest = json_decode( (string) file_get_contents( $manifest_path ), true );

			if ( isset( $manifest[ $entry_js ]['file'] ) ) {
				$script_src = VA_PLUGIN_URL . 'assets/dist/' . $manifest[ $entry_js ]['file'];
			}

			if ( ! empty( $manifest[ $entry_js ]['css'][0] ) ) {
				$style_src = VA_PLUGIN_URL . 'assets/dist/' . $manifest[ $entry_js ]['css'][0];
			}
		}

		if ( $style_src ) {
			wp_enqueue_style( 'va-app', $style_src, $style_deps, VA_VERSION );
		}

		return $script_src;
	}
}
