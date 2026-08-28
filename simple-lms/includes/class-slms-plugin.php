<?php
/**
 * Plugin bootstrapper - wires all sub-modules together.
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

final class SLMS_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var SLMS_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Loaded modules, keyed by short name.
	 *
	 * @var array
	 */
	private $modules = array();

	/**
	 * Retrieve the singleton.
	 *
	 * @return SLMS_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			// Set the instance first, then boot, so modules can safely call simple_lms().
			self::$instance->boot();
		}

		return self::$instance;
	}

	/**
	 * Empty constructor - see boot().
	 */
	private function __construct() {}

	/**
	 * Hook everything up.
	 */
	private function boot() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		$this->load_modules();
	}

	/**
	 * Instantiate and register modules.
	 */
	private function load_modules() {
		$this->modules['post_types']  = new SLMS_Post_Types();
		$this->modules['enrollment']  = new SLMS_Enrollment();
		$this->modules['progress']    = new SLMS_Progress();
		$this->modules['shortcodes']  = new SLMS_Shortcodes();
		$this->modules['templates']   = new SLMS_Templates();
		$this->modules['rest']        = new SLMS_REST_Controller();
		$this->modules['assets']      = new SLMS_Assets();

		if ( is_admin() ) {
			$this->modules['admin'] = new SLMS_Admin();
		}

		foreach ( $this->modules as $module ) {
			if ( method_exists( $module, 'register' ) ) {
				$module->register();
			}
		}

		/**
		 * Fires once all core modules are registered.
		 *
		 * @param SLMS_Plugin $plugin The plugin instance.
		 */
		do_action( 'slms_loaded', $this );
	}

	/**
	 * Access a loaded module.
	 *
	 * @param string $name Module key.
	 * @return object|null
	 */
	public function module( $name ) {
		return isset( $this->modules[ $name ] ) ? $this->modules[ $name ] : null;
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'simple-lms', false, dirname( SIMPLE_LMS_BASENAME ) . '/languages' );
	}
}
