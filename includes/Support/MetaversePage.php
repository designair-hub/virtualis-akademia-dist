<?php

namespace VirtualisAkademia\Support;

/**
 * Locates the single page embedding [va_metaverse], so redirect/URL-building
 * code doesn't need a hardcoded page ID or a dedicated settings field.
 */
class MetaversePage {

	private static ?int $page_id = null;
	private static bool $looked_up = false;

	public static function get_id(): ?int {
		if ( self::$looked_up ) {
			return self::$page_id;
		}

		self::$looked_up = true;

		$pages = get_posts( [
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		] );

		foreach ( $pages as $page ) {
			if ( has_shortcode( $page->post_content, 'va_metaverse' ) ) {
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
