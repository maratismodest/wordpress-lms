<?php
/**
 * Template loading for course / lesson single and archive views.
 *
 * A theme can override any of these by placing a file of the same name in
 * `yourtheme/simple-lms/`.
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

class SLMS_Templates {

	/**
	 * Hook registration.
	 */
	public function register() {
		add_filter( 'template_include', array( $this, 'template_include' ), 20 );
		add_filter( 'archive_template_hierarchy', array( $this, 'noop_hierarchy' ) );
	}

	/**
	 * Swap in a bundled template when the theme provides none.
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	public function template_include( $template ) {
		$candidate = '';

		if ( is_singular( SLMS_Post_Types::COURSE ) ) {
			$candidate = 'single-slms_course.php';
		} elseif ( is_singular( SLMS_Post_Types::LESSON ) ) {
			$candidate = 'single-slms_lesson.php';
		} elseif ( is_post_type_archive( SLMS_Post_Types::COURSE ) || is_tax( SLMS_Post_Types::CAT_TAX ) ) {
			$candidate = 'archive-slms_course.php';
		}

		if ( ! $candidate ) {
			return $template;
		}

		// Respect an explicit theme override picked up by the normal hierarchy.
		$theme_file = basename( $template );
		if ( in_array( $theme_file, array( 'single-slms_course.php', 'single-slms_lesson.php', 'archive-slms_course.php' ), true ) ) {
			return $template;
		}

		$found = slms_locate_template( $candidate );

		return $found ? $found : $template;
	}

	/**
	 * Placeholder filter kept for extension points; returns input unchanged.
	 *
	 * @param array $hierarchy Template hierarchy.
	 * @return array
	 */
	public function noop_hierarchy( $hierarchy ) {
		return $hierarchy;
	}
}
