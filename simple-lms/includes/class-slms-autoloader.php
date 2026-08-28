<?php
/**
 * PSR-ish autoloader for SLMS_* classes.
 *
 * Maps a class name like `SLMS_Post_Types` to `includes/class-slms-post-types.php`,
 * with admin/public specific classes resolved from their sub-directories.
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

class SLMS_Autoloader {

	/**
	 * Register the autoloader with SPL.
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Resolve and include a class file.
	 *
	 * @param string $class Fully-qualified class name.
	 */
	public static function autoload( $class ) {
		if ( 0 !== strpos( $class, 'SLMS_' ) ) {
			return;
		}

		$file = 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';

		$paths = array(
			SIMPLE_LMS_PATH . 'includes/' . $file,
			SIMPLE_LMS_PATH . 'admin/' . $file,
			SIMPLE_LMS_PATH . 'public/' . $file,
		);

		foreach ( $paths as $path ) {
			if ( is_readable( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
}
