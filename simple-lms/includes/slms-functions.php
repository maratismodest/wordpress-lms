<?php
/**
 * Procedural helper API for Simple LMS.
 *
 * These functions are the supported surface for themes and other plugins.
 * Every read is cheap and safe to call in a loop; writes fire action hooks.
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get merged plugin settings (defaults + saved option).
 *
 * @param string|null $key     Optional single setting to return.
 * @param mixed       $default Fallback when the key is missing.
 * @return mixed
 */
function slms_get_settings( $key = null, $default = null ) {
	$defaults = array(
		'courses_per_page'         => 12,
		'require_login'            => 1,
		'auto_enroll_free'         => 1,
		'course_slug'              => 'courses',
		'dashboard_page_id'        => 0,
		'delete_data_on_uninstall' => 0,
	);

	$settings = wp_parse_args( (array) get_option( 'slms_settings', array() ), $defaults );

	/**
	 * Filter the resolved settings array.
	 *
	 * @param array $settings Settings.
	 */
	$settings = apply_filters( 'slms_settings', $settings );

	if ( null === $key ) {
		return $settings;
	}

	return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
}

/**
 * Table name helper.
 *
 * @param string $name Bare table name (`enrollments` or `progress`).
 * @return string Prefixed table name.
 */
function slms_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'slms_' . $name;
}

/**
 * Get the course a lesson belongs to.
 *
 * @param int $lesson_id Lesson post ID.
 * @return int Course ID, or 0.
 */
function slms_get_lesson_course_id( $lesson_id ) {
	return absint( get_post_meta( $lesson_id, '_slms_course_id', true ) );
}

/**
 * Retrieve the ordered lessons of a course.
 *
 * @param int $course_id Course post ID.
 * @return WP_Post[] Lessons ordered by menu order then title.
 */
function slms_get_course_lessons( $course_id ) {
	$course_id = absint( $course_id );
	if ( ! $course_id ) {
		return array();
	}

	$cached = wp_cache_get( $course_id, 'slms_course_lessons' );
	if ( false !== $cached ) {
		return $cached;
	}

	$lessons = get_posts(
		array(
			'post_type'        => SLMS_Post_Types::LESSON,
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'orderby'          => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'meta_key'         => '_slms_course_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'       => $course_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'suppress_filters' => false,
		)
	);

	wp_cache_set( $course_id, $lessons, 'slms_course_lessons', HOUR_IN_SECONDS );

	return $lessons;
}

/**
 * Whether a course is free (price meta empty or zero).
 *
 * @param int $course_id Course ID.
 * @return bool
 */
function slms_course_is_free( $course_id ) {
	$price = get_post_meta( $course_id, '_slms_price', true );
	return ( '' === $price || 0 === (int) $price || 0.0 === (float) $price );
}

/**
 * Check whether a user is enrolled in a course.
 *
 * @param int $user_id   User ID.
 * @param int $course_id Course ID.
 * @return bool
 */
function slms_is_enrolled( $user_id, $course_id ) {
	global $wpdb;

	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );
	if ( ! $user_id || ! $course_id ) {
		return false;
	}

	$table = slms_table( 'enrollments' );
	$found = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$table} WHERE user_id = %d AND course_id = %d AND status = 'active' LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$user_id,
			$course_id
		)
	);

	return ! empty( $found );
}

/**
 * Enroll a user in a course (idempotent).
 *
 * @param int $user_id   User ID.
 * @param int $course_id Course ID.
 * @return true|WP_Error
 */
function slms_enroll_user( $user_id, $course_id ) {
	global $wpdb;

	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );

	if ( ! $user_id || get_post_type( $course_id ) !== SLMS_Post_Types::COURSE ) {
		return new WP_Error( 'slms_invalid', __( 'Invalid user or course.', 'simple-lms' ) );
	}

	if ( slms_is_enrolled( $user_id, $course_id ) ) {
		return true;
	}

	$table = slms_table( 'enrollments' );
	$now   = current_time( 'mysql', true );

	$result = $wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$table} (user_id, course_id, status, enrolled_at) VALUES (%d, %d, 'active', %s)
			 ON DUPLICATE KEY UPDATE status = 'active', completed_at = NULL", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$user_id,
			$course_id,
			$now
		)
	);

	if ( false === $result ) {
		return new WP_Error( 'slms_db_error', __( 'Could not save enrollment.', 'simple-lms' ) );
	}

	/**
	 * Fires after a user is enrolled in a course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 */
	do_action( 'slms_user_enrolled', $user_id, $course_id );

	return true;
}

/**
 * Remove a user's enrollment (and their progress) for a course.
 *
 * @param int $user_id   User ID.
 * @param int $course_id Course ID.
 * @return bool
 */
function slms_unenroll_user( $user_id, $course_id ) {
	global $wpdb;

	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );
	if ( ! $user_id || ! $course_id ) {
		return false;
	}

	$wpdb->delete( slms_table( 'enrollments' ), array( 'user_id' => $user_id, 'course_id' => $course_id ), array( '%d', '%d' ) );
	$wpdb->delete( slms_table( 'progress' ), array( 'user_id' => $user_id, 'course_id' => $course_id ), array( '%d', '%d' ) );

	/**
	 * Fires after a user is unenrolled from a course.
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 */
	do_action( 'slms_user_unenrolled', $user_id, $course_id );

	return true;
}

/**
 * Whether a lesson is marked complete for a user.
 *
 * @param int $user_id   User ID.
 * @param int $lesson_id Lesson ID.
 * @return bool
 */
function slms_is_lesson_complete( $user_id, $lesson_id ) {
	global $wpdb;

	$user_id   = absint( $user_id );
	$lesson_id = absint( $lesson_id );
	if ( ! $user_id || ! $lesson_id ) {
		return false;
	}

	$table = slms_table( 'progress' );
	$found = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$table} WHERE user_id = %d AND lesson_id = %d AND status = 'complete' LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$user_id,
			$lesson_id
		)
	);

	return ! empty( $found );
}

/**
 * Mark a lesson complete or incomplete for a user.
 *
 * @param int  $user_id   User ID.
 * @param int  $lesson_id Lesson ID.
 * @param bool $complete  True to complete, false to clear.
 * @return true|WP_Error
 */
function slms_set_lesson_complete( $user_id, $lesson_id, $complete = true ) {
	global $wpdb;

	$user_id   = absint( $user_id );
	$lesson_id = absint( $lesson_id );

	if ( ! $user_id || get_post_type( $lesson_id ) !== SLMS_Post_Types::LESSON ) {
		return new WP_Error( 'slms_invalid', __( 'Invalid user or lesson.', 'simple-lms' ) );
	}

	$course_id = slms_get_lesson_course_id( $lesson_id );
	$table     = slms_table( 'progress' );

	if ( $complete ) {
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (user_id, course_id, lesson_id, status, updated_at) VALUES (%d, %d, %d, 'complete', %s)
				 ON DUPLICATE KEY UPDATE status = 'complete', course_id = VALUES(course_id), updated_at = VALUES(updated_at)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$course_id,
				$lesson_id,
				current_time( 'mysql', true )
			)
		);
	} else {
		$result = $wpdb->delete(
			$table,
			array(
				'user_id'   => $user_id,
				'lesson_id' => $lesson_id,
			),
			array( '%d', '%d' )
		);
	}

	if ( false === $result ) {
		return new WP_Error( 'slms_db_error', __( 'Could not save progress.', 'simple-lms' ) );
	}

	wp_cache_delete( $user_id . ':' . $course_id, 'slms_progress' );

	/**
	 * Fires after a lesson's completion state changes.
	 *
	 * @param int  $user_id   User ID.
	 * @param int  $lesson_id Lesson ID.
	 * @param int  $course_id Course ID.
	 * @param bool $complete  New state.
	 */
	do_action( 'slms_lesson_progress_updated', $user_id, $lesson_id, $course_id, (bool) $complete );

	if ( $complete ) {
		/** This action is documented above with a narrower name for BC. */
		do_action( 'slms_lesson_completed', $user_id, $lesson_id, $course_id );
		slms_maybe_complete_course( $user_id, $course_id );
	}

	return true;
}

/**
 * Progress summary for a user in a course.
 *
 * @param int $user_id   User ID.
 * @param int $course_id Course ID.
 * @return array{percent:int,completed:int,total:int,complete:bool}
 */
function slms_get_course_progress( $user_id, $course_id ) {
	global $wpdb;

	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );

	$lessons = slms_get_course_lessons( $course_id );
	$total   = count( $lessons );

	$empty = array(
		'percent'   => 0,
		'completed' => 0,
		'total'     => $total,
		'complete'  => false,
	);

	if ( ! $user_id || ! $total ) {
		return $empty;
	}

	$cache_key = $user_id . ':' . $course_id;
	$completed = wp_cache_get( $cache_key, 'slms_progress' );

	if ( false === $completed ) {
		$lesson_ids   = wp_list_pluck( $lessons, 'ID' );
		$placeholders = implode( ',', array_fill( 0, $total, '%d' ) );
		$table        = slms_table( 'progress' );

		$completed = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND status = 'complete' AND lesson_id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				array_merge( array( $user_id ), $lesson_ids )
			)
		);

		wp_cache_set( $cache_key, $completed, 'slms_progress', HOUR_IN_SECONDS );
	}

	$completed = min( $completed, $total );
	$percent   = (int) round( ( $completed / $total ) * 100 );

	return array(
		'percent'   => $percent,
		'completed' => $completed,
		'total'     => $total,
		'complete'  => ( $completed === $total ),
	);
}

/**
 * Flip an enrollment to "completed" when every lesson is done.
 *
 * @param int $user_id   User ID.
 * @param int $course_id Course ID.
 */
function slms_maybe_complete_course( $user_id, $course_id ) {
	global $wpdb;

	if ( ! $course_id ) {
		return;
	}

	$progress = slms_get_course_progress( $user_id, $course_id );
	$table    = slms_table( 'enrollments' );

	if ( $progress['complete'] ) {
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET completed_at = %s WHERE user_id = %d AND course_id = %d AND completed_at IS NULL", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				current_time( 'mysql', true ),
				$user_id,
				$course_id
			)
		);

		if ( $updated ) {
			/**
			 * Fires the first time a user completes every lesson in a course.
			 *
			 * @param int $user_id   User ID.
			 * @param int $course_id Course ID.
			 */
			do_action( 'slms_course_completed', $user_id, $course_id );
		}
	} else {
		// Re-opened (a lesson was un-completed): clear the timestamp.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET completed_at = NULL WHERE user_id = %d AND course_id = %d AND completed_at IS NOT NULL", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$course_id
			)
		);
	}
}

/**
 * Courses a user is enrolled in, newest enrollment first.
 *
 * @param int $user_id User ID.
 * @return array[] List of rows: course_id, enrolled_at, completed_at, status.
 */
function slms_get_user_courses( $user_id ) {
	global $wpdb;

	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return array();
	}

	$table = slms_table( 'enrollments' );

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT course_id, status, enrolled_at, completed_at FROM {$table} WHERE user_id = %d ORDER BY enrolled_at DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$user_id
		),
		ARRAY_A
	);
}

/**
 * Number of active enrollments for a course.
 *
 * @param int $course_id Course ID.
 * @return int
 */
function slms_get_course_enrollment_count( $course_id ) {
	global $wpdb;

	$course_id = absint( $course_id );
	if ( ! $course_id ) {
		return 0;
	}

	$table = slms_table( 'enrollments' );

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE course_id = %d AND status = 'active'", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$course_id
		)
	);
}

/**
 * Whether a user may view a lesson's full content.
 *
 * @param int $user_id   User ID (0 for guests).
 * @param int $lesson_id Lesson ID.
 * @return bool
 */
function slms_user_can_access_lesson( $user_id, $lesson_id ) {
	$lesson_id = absint( $lesson_id );

	if ( get_post_meta( $lesson_id, '_slms_free_preview', true ) ) {
		return true;
	}

	$user_id = absint( $user_id );
	if ( ! $user_id ) {
		return false;
	}

	if ( user_can( $user_id, 'manage_slms' ) || user_can( $user_id, 'edit_others_slms_courses' ) ) {
		return true;
	}

	$course_id = slms_get_lesson_course_id( $lesson_id );

	return $course_id ? slms_is_enrolled( $user_id, $course_id ) : false;
}

/**
 * Get the previous / next lesson within a course.
 *
 * @param int    $lesson_id Current lesson ID.
 * @param string $which     'prev' or 'next'.
 * @return WP_Post|null
 */
function slms_get_adjacent_lesson( $lesson_id, $which = 'next' ) {
	$course_id = slms_get_lesson_course_id( $lesson_id );
	if ( ! $course_id ) {
		return null;
	}

	$lessons = slms_get_course_lessons( $course_id );
	$ids     = wp_list_pluck( $lessons, 'ID' );
	$index   = array_search( (int) $lesson_id, $ids, true );

	if ( false === $index ) {
		return null;
	}

	$target = ( 'prev' === $which ) ? $index - 1 : $index + 1;

	return isset( $lessons[ $target ] ) ? $lessons[ $target ] : null;
}

/**
 * Locate a template, honouring theme overrides in `yourtheme/simple-lms/`.
 *
 * @param string $name Template file name, e.g. `parts/course-card.php`.
 * @return string Absolute path, or '' when not found.
 */
function slms_locate_template( $name ) {
	$name = ltrim( $name, '/' );

	$theme = locate_template(
		array(
			'simple-lms/' . $name,
			$name,
		)
	);

	if ( $theme ) {
		return $theme;
	}

	$plugin = SIMPLE_LMS_PATH . 'templates/' . $name;

	return file_exists( $plugin ) ? $plugin : '';
}

/**
 * Render a template part with scoped variables.
 *
 * @param string $name Template file name.
 * @param array  $args Variables extracted into the template scope.
 * @param bool   $echo Whether to print (default) or return the markup.
 * @return string
 */
function slms_get_template( $name, $args = array(), $echo = true ) {
	$file = slms_locate_template( $name );
	if ( ! $file ) {
		return '';
	}

	if ( ! empty( $args ) ) {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $args, EXTR_SKIP );
	}

	ob_start();
	include $file;
	$html = ob_get_clean();

	if ( $echo ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template escapes its own output.
		return '';
	}

	return $html;
}

/**
 * Format a progress bar as HTML.
 *
 * @param array $progress Result of slms_get_course_progress().
 * @return string
 */
function slms_progress_bar_html( $progress ) {
	return slms_get_template( 'parts/progress-bar.php', array( 'progress' => $progress ), false );
}
