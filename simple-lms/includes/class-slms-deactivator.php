<?php
/**
 * Runs on plugin deactivation.
 *
 * Intentionally non-destructive: tables, posts and settings are preserved so a
 * re-activation restores the site. Data removal happens only via uninstall.php.
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

class SLMS_Deactivator {

	/**
	 * Deactivation entry point.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
		delete_transient( 'slms_activation_redirect' );
	}
}
