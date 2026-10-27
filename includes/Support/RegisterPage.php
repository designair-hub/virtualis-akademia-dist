<?php

namespace VirtualisAkademia\Support;

/**
 * Locates the single public page embedding [va_register], the same way
 * HomePage locates [va_landing] -- the registration form grew too many
 * fields (see Activation::ensure_participation_data_fields()) to stay
 * comfortably inside the landing page's compact auth tab, so it gets its
 * own full-width page instead.
 */
class RegisterPage {

	private static ?int $page_id = null;
	private static bool $looked_up = false;

	public static function get_id(): ?int {
		if ( self::$looked_up ) {
			return self::$page_id;
		}

		self::$looked_up = true;

		// Activation::ensure_register_page() records the page it created
		// here -- checked first since it's a single direct option lookup,
		// no query involved. Falls through to the shortcode scan below for
		// a page a client created by hand (or on a site provisioned before
		// this option existed).
		$stored_id = (int) get_option( 'va_register_page_id' );

		if ( $stored_id && 'publish' === get_post_status( $stored_id ) ) {
			self::$page_id = $stored_id;

			return self::$page_id;
		}

		$pages = get_posts( [
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		] );

		foreach ( $pages as $page ) {
			if ( has_shortcode( $page->post_content, 'va_register' ) ) {
				self::$page_id = $page->ID;
				break;
			}
		}

		return self::$page_id;
	}

	public static function get_url(): ?string {
		$page_id = self::get_id();

		return $page_id ? get_permalink( $page_id ) : null;
	}
}
