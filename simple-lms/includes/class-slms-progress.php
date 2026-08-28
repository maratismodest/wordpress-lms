<?php
/**
 * Progress: the admin-ajax endpoint for toggling lesson completion.
 *
 * Course-completion roll-up lives in slms_maybe_complete_course() (functions file),
 * which is invoked from slms_set_lesson_complete().
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

class SLMS_Progress {

	/**
	 * Hook registration.
	 */
	public function register() {
		add_action( 'wp_ajax_slms_complete_lesson', array( $this, 'ajax_complete_lesson' ) );

		// Append the completion control + prev/next nav to accessible lessons so the
		// feature works in any theme, not only the bundled template.
		add_filter( 'the_content', array( $this, 'append_lesson_controls' ), 25 );

		// Keep the per-course lesson cache fresh when lessons change.
		add_action( 'save_post_' . SLMS_Post_Types::LESSON, array( $this, 'bust_lesson_cache' ) );
		add_action( 'deleted_post', array( $this, 'bust_lesson_cache' ) );
	}

	/**
	 * Append the "mark complete" button and lesson navigation.
	 *
	 * Skipped when the bundled template already rendered them (it sets a flag) or
	 * when the viewer cannot access the lesson (the enrollment gate handles that).
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function append_lesson_controls( $content ) {
		if ( ! is_singular( SLMS_Post_Types::LESSON ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$lesson_id = get_the_ID();

		if ( ! slms_user_can_access_lesson( get_current_user_id(), $lesson_id ) ) {
			return $content;
		}

		$content .= slms_get_template( 'parts/lesson-complete-button.php', array( 'lesson_id' => $lesson_id ), false );
		$content .= slms_get_template( 'parts/lesson-nav.php', array( 'lesson_id' => $lesson_id ), false );

		return $content;
	}

	/**
	 * admin-ajax handler: mark a lesson complete or incomplete for the current user.
	 */
	public function ajax_complete_lesson() {
		check_ajax_referer( 'slms_frontend', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Please log in first.', 'simple-lms' ) ), 401 );
		}

		$lesson_id = isset( $_POST['lesson_id'] ) ? absint( $_POST['lesson_id'] ) : 0;
		$complete  = ! isset( $_POST['complete'] ) || (bool) absint( $_POST['complete'] );
		$user_id   = get_current_user_id();

		if ( ! slms_user_can_access_lesson( $user_id, $lesson_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have access to this lesson.', 'simple-lms' ) ), 403 );
		}

		$result = slms_set_lesson_complete( $user_id, $lesson_id, $complete );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}

		$course_id = slms_get_lesson_course_id( $lesson_id );

		wp_send_json_success(
			array(
				'complete' => $complete,
				'progress' => slms_get_course_progress( $user_id, $course_id ),
			)
		);
	}

	/**
	 * Clear the cached lesson list for a lesson's course.
	 *
	 * @param int $post_id Post ID being saved or deleted.
	 */
	public function bust_lesson_cache( $post_id ) {
		if ( get_post_type( $post_id ) !== SLMS_Post_Types::LESSON ) {
			return;
		}
		$course_id = slms_get_lesson_course_id( $post_id );
		if ( $course_id ) {
			wp_cache_delete( $course_id, 'slms_course_lessons' );
		}
	}
}
