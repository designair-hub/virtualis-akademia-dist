<?php

namespace VirtualisAkademia\Support;

/**
 * The Terms of Use page ("Felhasználási feltételek"): an ordinary page the
 * client writes in the editor, found by its address (/felhasznalasi-feltetelek/).
 * Public like the privacy policy, linked from the footer; rendered in the
 * product's look by Frontend\BrandedShell.
 */
class TermsPage {

	public const SLUG = 'felhasznalasi-feltetelek';

	public static function get_id(): ?int {
		$page = get_page_by_path( self::SLUG, OBJECT, 'page' );

		return $page && 'publish' === $page->post_status ? (int) $page->ID : null;
	}

	public static function get_url(): ?string {
		$id = self::get_id();

		return $id ? get_permalink( $id ) : null;
	}
}
