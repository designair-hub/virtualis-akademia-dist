<?php

namespace VirtualisAkademia\Frontend;

use VirtualisAkademia\Hookable;

class ShortcodeMetaverse implements Hookable {

	public function register(): void {
		add_shortcode( 'va_metaverse', [ $this, 'render' ] );
		add_filter( 'document_title_parts', [ $this, 'document_title' ] );
	}

	/**
	 * The page is titled like the site ("Virtuális Akadémia") -- the browser
	 * tab shouldn't read "Virtuális Akadémia – Virtuális Akadémia".
	 */
	public function document_title( $parts ) {
		$page_id = \VirtualisAkademia\Support\MetaversePage::get_id();

		if ( $page_id && is_page( $page_id ) && isset( $parts['title'], $parts['site'] ) && $parts['title'] === $parts['site'] ) {
			unset( $parts['site'] );
		}

		return $parts;
	}

	public function render(): string {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'A tartalom megtekintéséhez be kell jelentkezned.', 'virtualis-akademia' ) . '</p>';
		}

		// Pannellum's own fullscreen button only ever fullscreens the viewer
		// element it was constructed on (it clears and rebuilds that
		// element's innerHTML itself, so the HUD pieces can't live inside it)
		// -- this wrapper is our own fullscreen target instead (see Hud.js's
		// fullscreen button), so the header/minimap/mute-help bar stay
		// visible and clickable in fullscreen too.
		return '<div id="va-metaverse-root" class="va-metaverse-root">' .
			'<div id="va-metaverse-viewer" class="va-metaverse-viewer"></div>' .
			'<div id="va-metaverse-header" class="va-header"></div>' .
			'<div id="va-metaverse-minimap" class="va-metaverse-minimap"></div>' .
			'<div id="va-metaverse-hud" class="va-metaverse-hud"></div>' .
			'</div>';
	}
}
