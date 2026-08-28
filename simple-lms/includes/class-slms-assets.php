<?php
/**
 * Front-end and admin asset registration.
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

class SLMS_Assets {

	/**
	 * Hook registration.
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin' ) );
	}

	/**
	 * Whether the current request should load front-end assets.
	 *
	 * @return bool
	 */
	private function is_lms_context() {
		if ( is_singular( SLMS_Post_Types::all() ) || is_post_type_archive( SLMS_Post_Types::COURSE ) || is_tax( SLMS_Post_Types::CAT_TAX ) ) {
			return true;
		}

		$post = get_post();
		if ( $post instanceof WP_Post ) {
			$shortcodes = simple_lms()->module( 'shortcodes' );
			if ( $shortcodes ) {
				foreach ( $shortcodes->tags() as $tag ) {
					if ( has_shortcode( $post->post_content, $tag ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Enqueue front-end CSS/JS and hand data to the script.
	 */
	public function frontend() {
		if ( ! $this->is_lms_context() ) {
			return;
		}

		wp_enqueue_style(
			'slms-frontend',
			SIMPLE_LMS_URL . 'assets/css/slms-frontend.css',
			array(),
			SIMPLE_LMS_VERSION
		);

		wp_enqueue_script(
			'slms-frontend',
			SIMPLE_LMS_URL . 'assets/js/slms-frontend.js',
			array(),
			SIMPLE_LMS_VERSION,
			true
		);

		wp_localize_script(
			'slms-frontend',
			'SLMS',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'restUrl'   => esc_url_raw( rest_url( SLMS_REST_Controller::NS ) ),
				'nonce'     => wp_create_nonce( 'slms_frontend' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'loggedIn'  => is_user_logged_in(),
				'i18n'      => array(
					'enrolling' => __( 'Enrolling…', 'simple-lms' ),
					'enrolled'  => __( 'Enrolled', 'simple-lms' ),
					'continue'  => __( 'Continue', 'simple-lms' ),
					'saving'    => __( 'Saving…', 'simple-lms' ),
					'completed' => __( 'Completed', 'simple-lms' ),
					'markDone'  => __( 'Mark as complete', 'simple-lms' ),
					'error'     => __( 'Something went wrong. Please try again.', 'simple-lms' ),
					'loginFirst'=> __( 'Please log in to continue.', 'simple-lms' ),
				),
			)
		);
	}

	/**
	 * Enqueue admin CSS/JS only on the plugin's own screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function admin( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		$is_plugin_screen = $screen && (
			in_array( $screen->post_type, SLMS_Post_Types::all(), true )
			|| false !== strpos( (string) $hook, 'slms' )
		);

		if ( ! $is_plugin_screen ) {
			return;
		}

		wp_enqueue_style(
			'slms-admin',
			SIMPLE_LMS_URL . 'assets/css/slms-admin.css',
			array(),
			SIMPLE_LMS_VERSION
		);

		wp_enqueue_script(
			'slms-admin',
			SIMPLE_LMS_URL . 'assets/js/slms-admin.js',
			array(),
			SIMPLE_LMS_VERSION,
			true
		);
	}
}
