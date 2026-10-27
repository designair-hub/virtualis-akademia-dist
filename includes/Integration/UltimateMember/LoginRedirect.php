<?php

namespace VirtualisAkademia\Integration\UltimateMember;

use VirtualisAkademia\Hookable;
use VirtualisAkademia\Support\MetaversePage;

/**
 * The only real destination on this site is the metaverse -- send members
 * straight there after logging in from the landing page's embedded form,
 * rather than Ultimate Member's own default (the referring page, i.e. back
 * to the landing page they just left).
 */
class LoginRedirect implements Hookable {

	public function register(): void {
		add_filter( 'um_login_redirect_url', [ $this, 'redirect_to_metaverse' ] );
	}

	public function redirect_to_metaverse( string $url ): string {
		return MetaversePage::get_url() ?: $url;
	}
}
