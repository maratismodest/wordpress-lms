<?php
/**
 * Plugin Name:       Simple LMS
 * Plugin URI:        https://github.com/maratismodest/wordpress-lms
 * Description:        Lightweight Learning Management System for WordPress: courses, lessons, enrollments and progress tracking.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Marat Faizerakhmanov
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       simple-lms
 * Domain Path:       /languages
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

define( 'SIMPLE_LMS_VERSION', '1.0.0' );
define( 'SIMPLE_LMS_FILE', __FILE__ );
define( 'SIMPLE_LMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'SIMPLE_LMS_URL', plugin_dir_url( __FILE__ ) );
define( 'SIMPLE_LMS_BASENAME', plugin_basename( __FILE__ ) );

require_once SIMPLE_LMS_PATH . 'includes/class-slms-autoloader.php';
SLMS_Autoloader::register();

// Procedural helper API (not class-based, so loaded directly).
require_once SIMPLE_LMS_PATH . 'includes/slms-functions.php';

/**
 * Main plugin instance.
 *
 * @return SLMS_Plugin
 */
function simple_lms() {
	return SLMS_Plugin::instance();
}

// Lifecycle hooks.
register_activation_hook( __FILE__, array( 'SLMS_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SLMS_Deactivator', 'deactivate' ) );

// Bootstrap.
add_action( 'plugins_loaded', 'simple_lms', 5 );
