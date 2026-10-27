<?php
/**
 * Plugin Name:       Virtuális Akadémia
 * Description:       3D panoráma alapú virtuális akadémia (LMS) LearnDash, Ultimate Member és Pannellum.js integrációval.
 * Version:           1.0.5
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Pap Balázs
 * Author URI:        https://designair.hu/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       virtualis-akademia
 * Domain Path:       /languages
 */

namespace VirtualisAkademia;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VA_PLUGIN_FILE', __FILE__ );
define( 'VA_PLUGIN_DIR', __DIR__ );
define( 'VA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VA_VERSION', '1.0.5' );

$va_autoload = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $va_autoload ) ) {
	require_once $va_autoload;
}

/**
 * Two of the LearnDash add-ons this site runs (learndash-integrity,
 * learndash-achievements) load their text domain before `init`, which WP
 * 6.7+ flags via `_doing_it_wrong( '_load_textdomain_just_in_time' )`. With
 * WP_DEBUG on that becomes an E_USER_NOTICE on essentially every request --
 * harmless in the page, but Duplicator Pro's build-time error handler treats
 * any PHP notice raised mid-package as a fatal "[PHP ERR][ISSUE]" and aborts
 * the backup on the first database table. Suppress that one specific nag
 * (only that function -- every other doing-it-wrong warning still fires) so
 * backups and other long-running admin tooling aren't held hostage by a
 * third-party plugin's load-order quirk. WP_DEBUG-only; production is
 * unaffected since the notice never fires there.
 */
add_filter(
	'doing_it_wrong_trigger_error',
	static function ( $trigger, $function_name = '' ) {
		return '_load_textdomain_just_in_time' === $function_name ? false : $trigger;
	},
	10,
	2
);

register_activation_hook( __FILE__, [ Activation::class, 'run' ] );
register_deactivation_hook( __FILE__, [ Deactivation::class, 'run' ] );

add_action( 'plugins_loaded', static function () {
	Plugin::instance()->boot();
} );
