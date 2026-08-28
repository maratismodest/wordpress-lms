<?php
/**
 * REST API: namespace `slms/v1`.
 *
 * Endpoints:
 *   POST slms/v1/enroll                          { course_id }
 *   POST slms/v1/lessons/<id>/complete           { complete: bool }
 *   GET  slms/v1/courses/<id>/progress
 *
 * Auth: a logged-in user plus a valid `wp_rest` nonce (the default cookie scheme).
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

class SLMS_REST_Controller {

	const NS = 'slms/v1';

	/**
	 * Hook registration.
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Declare routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/enroll',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'enroll' ),
				'permission_callback' => array( $this, 'require_login' ),
				'args'                => array(
					'course_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/lessons/(?P<id>\d+)/complete',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'complete_lesson' ),
				'permission_callback' => array( $this, 'require_login' ),
				'args'                => array(
					'id'       => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'complete' => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/courses/(?P<id>\d+)/progress',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_progress' ),
				'permission_callback' => array( $this, 'require_login' ),
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Shared permission check: logged in.
	 *
	 * @return true|WP_Error
	 */
	public function require_login() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'slms_rest_forbidden', __( 'Authentication required.', 'simple-lms' ), array( 'status' => 401 ) );
		}
		return true;
	}

	/**
	 * POST /enroll
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function enroll( WP_REST_Request $request ) {
		$course_id = $request['course_id'];
		$user_id   = get_current_user_id();

		if ( ! slms_course_is_free( $course_id ) && ! current_user_can( 'manage_slms' ) ) {
			return new WP_Error( 'slms_not_free', __( 'This course is not open for free enrollment.', 'simple-lms' ), array( 'status' => 403 ) );
		}

		$result = slms_enroll_user( $user_id, $course_id );

		if ( is_wp_error( $result ) ) {
			$result->add_data( array( 'status' => 400 ) );
			return $result;
		}

		return rest_ensure_response(
			array(
				'enrolled' => true,
				'progress' => slms_get_course_progress( $user_id, $course_id ),
			)
		);
	}

	/**
	 * POST /lessons/<id>/complete
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function complete_lesson( WP_REST_Request $request ) {
		$lesson_id = (int) $request['id'];
		$complete  = (bool) $request['complete'];
		$user_id   = get_current_user_id();

		if ( ! slms_user_can_access_lesson( $user_id, $lesson_id ) ) {
			return new WP_Error( 'slms_rest_forbidden', __( 'You do not have access to this lesson.', 'simple-lms' ), array( 'status' => 403 ) );
		}

		$result = slms_set_lesson_complete( $user_id, $lesson_id, $complete );

		if ( is_wp_error( $result ) ) {
			$result->add_data( array( 'status' => 400 ) );
			return $result;
		}

		return rest_ensure_response(
			array(
				'complete' => $complete,
				'progress' => slms_get_course_progress( $user_id, slms_get_lesson_course_id( $lesson_id ) ),
			)
		);
	}

	/**
	 * GET /courses/<id>/progress
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_progress( WP_REST_Request $request ) {
		$course_id = (int) $request['id'];

		return rest_ensure_response(
			slms_get_course_progress( get_current_user_id(), $course_id )
		);
	}
}
