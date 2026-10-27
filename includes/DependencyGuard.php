<?php

namespace VirtualisAkademia;

use VirtualisAkademia\Support\Dependencies;

/**
 * WordPress core re-marks a plugin active right after firing its activation
 * hook, so calling deactivate_plugins() from register_activation_hook alone
 * gets silently overwritten. Checking again on admin_init is the reliable
 * way to enforce the LearnDash/Ultimate Member dependency, and it also
 * catches the case where one of them is deactivated later.
 */
class DependencyGuard implements Hookable {

	private const NOTICE_TRANSIENT = 'va_dependency_notice';

	public function register(): void {
		add_action( 'admin_init', [ $this, 'check' ] );
		add_action( 'admin_notices', [ $this, 'render_notice' ] );
	}

	public function check(): void {
		if ( Dependencies::active() ) {
			return;
		}

		if ( ! is_plugin_active( plugin_basename( VA_PLUGIN_FILE ) ) ) {
			return;
		}

		deactivate_plugins( plugin_basename( VA_PLUGIN_FILE ) );
		set_transient( self::NOTICE_TRANSIENT, true, 60 );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}

	public function render_notice(): void {
		if ( ! get_transient( self::NOTICE_TRANSIENT ) ) {
			return;
		}

		delete_transient( self::NOTICE_TRANSIENT );

		echo '<div class="notice notice-error"><p>' .
			esc_html__( 'A Virtuális Akadémia plugin aktiválásához a LearnDash és az Ultimate Member pluginoknak is aktívnak kell lenniük.', 'virtualis-akademia' ) .
			'</p></div>';
	}
}
