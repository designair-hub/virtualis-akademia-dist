<?php

namespace VirtualisAkademia\Integration\UltimateMember;

use VirtualisAkademia\Hookable;
use VirtualisAkademia\Support\HomePage;
use VirtualisAkademia\Support\RegisterPage;

/**
 * The whole site is closed to logged-out visitors except the one public
 * entry point: the landing page (intro + embedded login/register forms) and
 * Ultimate Member's own login/register/password-reset utility pages, which
 * have to stay reachable for the landing page's forms to work at all.
 *
 * Ultimate Member's own Content Restriction (configured per-page in the
 * admin, e.g. on the metaverse page) remains the primary, client-editable
 * gate for that specific content; this is the site-wide backstop so nothing
 * -- including default WordPress pages/posts nobody thought to restrict --
 * is reachable by an anonymous visitor.
 */
class AccessGate implements Hookable {

	private const EXEMPT_UM_CORE_PAGES = [ 'login', 'register', 'password-reset', 'logout' ];

	public function register(): void {
		add_action( 'template_redirect', [ $this, 'maybe_block' ] );
	}

	public function maybe_block(): void {
		if ( is_user_logged_in() || is_admin() ) {
			return;
		}

		if ( $this->is_exempt() ) {
			return;
		}

		wp_safe_redirect( HomePage::get_url() ?: home_url( '/' ) );
		exit;
	}

	private function is_exempt(): bool {
		$home_page_id = HomePage::get_id();

		if ( $home_page_id && is_page( $home_page_id ) ) {
			return true;
		}

		$register_page_id = RegisterPage::get_id();

		if ( $register_page_id && is_page( $register_page_id ) ) {
			return true;
		}

		$privacy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );

		if ( $privacy_page_id && is_page( $privacy_page_id ) ) {
			return true;
		}

		// Terms of use: public, like the privacy policy.
		$terms_page_id = \VirtualisAkademia\Support\TermsPage::get_id();

		if ( $terms_page_id && is_page( $terms_page_id ) ) {
			return true;
		}

		$queried_id = get_queried_object_id();

		if ( ! $queried_id ) {
			return false;
		}

		$um_core = get_post_meta( $queried_id, '_um_core', true );

		return in_array( $um_core, self::EXEMPT_UM_CORE_PAGES, true );
	}
}
