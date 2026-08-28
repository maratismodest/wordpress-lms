<?php
/**
 * Enrollment: the admin-ajax endpoint, free-course auto-enroll, and lesson gating.
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

class SLMS_Enrollment {

	/**
	 * Hook registration.
	 */
	public function register() {
		add_action( 'wp_ajax_slms_enroll', array( $this, 'ajax_enroll' ) );
		add_action( 'template_redirect', array( $this, 'maybe_require_login' ) );
		add_action( 'template_redirect', array( $this, 'maybe_auto_enroll' ) );
		add_filter( 'the_content', array( $this, 'gate_lesson_content' ), 20 );
	}

	/**
	 * When "Require login to view lessons" is on, bounce guests to wp-login
	 * unless the lesson is a free preview.
	 */
	public function maybe_require_login() {
		if ( ! is_singular( SLMS_Post_Types::LESSON ) || is_user_logged_in() ) {
			return;
		}

		if ( ! slms_get_settings( 'require_login' ) ) {
			return;
		}

		$lesson_id = get_queried_object_id();
		if ( get_post_meta( $lesson_id, '_slms_free_preview', true ) ) {
			return;
		}

		wp_safe_redirect( wp_login_url( get_permalink( $lesson_id ) ) );
		exit;
	}

	/**
	 * admin-ajax handler: enroll the current user in a course.
	 */
	public function ajax_enroll() {
		check_ajax_referer( 'slms_frontend', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please log in to enroll.', 'simple-lms' ) ), 401 );
		}

		$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
		$user_id   = get_current_user_id();

		if ( ! slms_course_is_free( $course_id ) && ! current_user_can( 'manage_slms' ) ) {
			wp_send_json_error( array( 'message' => __( 'This course is not open for free enrollment.', 'simple-lms' ) ), 403 );
		}

		$result = slms_enroll_user( $user_id, $course_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		wp_send_json_success(
			array(
				'message'  => __( 'You are enrolled.', 'simple-lms' ),
				'enrolled' => true,
				'progress' => slms_get_course_progress( $user_id, $course_id ),
			)
		);
	}

	/**
	 * Enroll logged-in visitors automatically when they open a free course and
	 * the "auto enroll free" setting is on.
	 */
	public function maybe_auto_enroll() {
		if ( ! is_singular( SLMS_Post_Types::COURSE ) || ! is_user_logged_in() ) {
			return;
		}

		if ( ! slms_get_settings( 'auto_enroll_free' ) ) {
			return;
		}

		$course_id = get_queried_object_id();

		if ( slms_course_is_free( $course_id ) && ! slms_is_enrolled( get_current_user_id(), $course_id ) ) {
			slms_enroll_user( get_current_user_id(), $course_id );
		}
	}

	/**
	 * Replace a locked lesson's body with a teaser and an enroll CTA.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function gate_lesson_content( $content ) {
		if ( ! is_singular( SLMS_Post_Types::LESSON ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$lesson_id = get_the_ID();

		if ( slms_user_can_access_lesson( get_current_user_id(), $lesson_id ) ) {
			return $content;
		}

		$course_id = slms_get_lesson_course_id( $lesson_id );

		return slms_get_template(
			'parts/lesson-locked.php',
			array(
				'lesson_id' => $lesson_id,
				'course_id' => $course_id,
				'excerpt'   => wp_trim_words( wp_strip_all_tags( $content ), 55 ),
			),
			false
		);
	}
}
