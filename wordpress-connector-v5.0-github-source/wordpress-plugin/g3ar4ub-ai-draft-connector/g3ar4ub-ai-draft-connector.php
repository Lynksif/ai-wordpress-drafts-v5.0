<?php
/**
 * Plugin Name: AI Multi-Site Draft Connector
 * Plugin URI:  https://g3ar4ub.com/
 * Description: Secure, draft-only REST connector for built-in and custom editorial websites.
 * Version:     5.0.0
 * Author:      G3AR4UB
 * Author URI:  https://g3ar4ub.com/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: g3ar4ub-ai-draft-connector
 * Requires at least: 6.4
 * Requires PHP: 8.0
 */

defined( 'ABSPATH' ) || exit;

define( 'G3AI_VERSION', '5.0.0' );
define( 'G3AI_FILE', __FILE__ );
define( 'G3AI_DIR', plugin_dir_path( __FILE__ ) );
define( 'G3AI_URL', plugin_dir_url( __FILE__ ) );
define( 'G3AI_OPTION', 'g3ai_settings' );

require_once G3AI_DIR . 'includes/class-g3ai-site-profile.php';
require_once G3AI_DIR . 'includes/class-g3ai-seo-adapter.php';
require_once G3AI_DIR . 'includes/class-g3ai-activator.php';
require_once G3AI_DIR . 'includes/class-g3ai-rest-controller.php';
require_once G3AI_DIR . 'includes/class-g3ai-admin.php';
require_once G3AI_DIR . 'includes/class-g3ai-renderer.php';

register_activation_hook( __FILE__, array( 'G3AI_Activator', 'activate' ) );

/**
 * Boots the connector after WordPress has loaded active plugins.
 */
function g3ai_boot_plugin() {
	G3AI_Activator::maybe_upgrade();

	$rest = new G3AI_REST_Controller();
	$rest->register_hooks();

	$renderer = new G3AI_Renderer();
	$renderer->register_hooks();

	if ( is_admin() ) {
		$admin = new G3AI_Admin();
		$admin->register_hooks();
	}
}
add_action( 'plugins_loaded', 'g3ai_boot_plugin' );
