<?php
/**
 * Front-end shortcodes.
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

class SLMS_Shortcodes {

	/**
	 * Shortcode tag => callback map.
	 *
	 * @var array
	 */
	private $tags = array(
		'slms_courses'         => 'render_courses',
		'slms_my_courses'      => 'render_my_courses',
		'slms_course_progress' => 'render_course_progress',
		'slms_enroll_button'   => 'render_enroll_button',
		'slms_lesson_list'     => 'render_lesson_list',
	);

	/**
	 * Register every shortcode.
	 */
	public function register() {
		foreach ( $this->tags as $tag => $method ) {
			add_shortcode( $tag, array( $this, $method ) );
		}
	}

	/**
	 * All tags handled here (used by the asset loader).
	 *
	 * @return string[]
	 */
	public function tags() {
		return array_keys( $this->tags );
	}

	/**
	 * [slms_courses] - a responsive grid of course cards.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_courses( $atts ) {
		$atts = shortcode_atts(
			array(
				'category' => '',
				'per_page' => (int) slms_get_settings( 'courses_per_page' ),
				'columns'  => 3,
				'orderby'  => 'date',
				'order'    => 'DESC',
			),
			$atts,
			'slms_courses'
		);

		$paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );

		$query_args = array(
			'post_type'      => SLMS_Post_Types::COURSE,
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, (int) $atts['per_page'] ),
			'paged'          => $paged,
			'orderby'        => sanitize_key( $atts['orderby'] ),
			'order'          => ( 'ASC' === strtoupper( $atts['order'] ) ) ? 'ASC' : 'DESC',
		);

		if ( '' !== $atts['category'] ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => SLMS_Post_Types::CAT_TAX,
					'field'    => 'slug',
					'terms'    => array_map( 'sanitize_title', array_map( 'trim', explode( ',', $atts['category'] ) ) ),
				),
			);
		}

		$query = new WP_Query( $query_args );

		return slms_get_template(
			'shortcodes/courses.php',
			array(
				'query'   => $query,
				'columns' => max( 1, min( 6, (int) $atts['columns'] ) ),
			),
			false
		);
	}

	/**
	 * [slms_my_courses] - the current user's enrolled courses with progress.
	 *
	 * @return string
	 */
	public function render_my_courses() {
		if ( ! is_user_logged_in() ) {
			return slms_get_template( 'shortcodes/login-required.php', array(), false );
		}

		$user_id      = get_current_user_id();
		$enrollments  = slms_get_user_courses( $user_id );

		return slms_get_template(
			'shortcodes/my-courses.php',
			array(
				'user_id'     => $user_id,
				'enrollments' => $enrollments,
			),
			false
		);
	}

	/**
	 * [slms_course_progress id="123"] - a single progress bar.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_course_progress( $atts ) {
		$atts      = shortcode_atts( array( 'id' => 0 ), $atts, 'slms_course_progress' );
		$course_id = $this->resolve_course_id( $atts['id'] );

		if ( ! $course_id || ! is_user_logged_in() ) {
			return '';
		}

		return slms_progress_bar_html( slms_get_course_progress( get_current_user_id(), $course_id ) );
	}

	/**
	 * [slms_enroll_button id="123"] - enroll / continue button.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_enroll_button( $atts ) {
		$atts      = shortcode_atts( array( 'id' => 0 ), $atts, 'slms_enroll_button' );
		$course_id = $this->resolve_course_id( $atts['id'] );

		if ( ! $course_id ) {
			return '';
		}

		$user_id  = get_current_user_id();
		$enrolled = $user_id ? slms_is_enrolled( $user_id, $course_id ) : false;

		return slms_get_template(
			'parts/enroll-button.php',
			array(
				'course_id' => $course_id,
				'enrolled'  => $enrolled,
				'is_free'   => slms_course_is_free( $course_id ),
			),
			false
		);
	}

	/**
	 * [slms_lesson_list id="123"] - the course curriculum with completion ticks.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_lesson_list( $atts ) {
		$atts      = shortcode_atts( array( 'id' => 0 ), $atts, 'slms_lesson_list' );
		$course_id = $this->resolve_course_id( $atts['id'] );

		if ( ! $course_id ) {
			return '';
		}

		return slms_get_template(
			'parts/lesson-list.php',
			array(
				'course_id' => $course_id,
				'lessons'   => slms_get_course_lessons( $course_id ),
				'user_id'   => get_current_user_id(),
			),
			false
		);
	}

	/**
	 * Resolve an explicit course ID, or infer it from the current post.
	 *
	 * @param int $id Provided attribute.
	 * @return int
	 */
	private function resolve_course_id( $id ) {
		$id = absint( $id );
		if ( $id ) {
			return $id;
		}

		$post = get_post();
		if ( ! $post ) {
			return 0;
		}

		if ( SLMS_Post_Types::COURSE === $post->post_type ) {
			return $post->ID;
		}

		if ( SLMS_Post_Types::LESSON === $post->post_type ) {
			return slms_get_lesson_course_id( $post->ID );
		}

		return 0;
	}
}
