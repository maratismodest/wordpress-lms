<?php
/**
 * Registers the Course and Lesson custom post types and the course category taxonomy.
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

class SLMS_Post_Types {

	const COURSE  = 'slms_course';
	const LESSON  = 'slms_lesson';
	const CAT_TAX = 'slms_course_cat';

	/**
	 * Hook registration.
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_taxonomies' ), 9 );
		add_action( 'init', array( $this, 'register_post_types' ), 10 );
		add_action( 'admin_init', array( 'SLMS_Activator', 'maybe_upgrade' ) );
	}

	/**
	 * Course category taxonomy.
	 */
	public function register_taxonomies() {
		register_taxonomy(
			self::CAT_TAX,
			array( self::COURSE ),
			array(
				'labels'            => array(
					'name'          => __( 'Course Categories', 'simple-lms' ),
					'singular_name' => __( 'Course Category', 'simple-lms' ),
					'search_items'  => __( 'Search Categories', 'simple-lms' ),
					'all_items'     => __( 'All Categories', 'simple-lms' ),
					'edit_item'     => __( 'Edit Category', 'simple-lms' ),
					'update_item'   => __( 'Update Category', 'simple-lms' ),
					'add_new_item'  => __( 'Add New Category', 'simple-lms' ),
					'new_item_name' => __( 'New Category Name', 'simple-lms' ),
					'menu_name'     => __( 'Categories', 'simple-lms' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'course-category' ),
			)
		);
	}

	/**
	 * Course + Lesson CPTs.
	 */
	public function register_post_types() {
		$settings   = slms_get_settings();
		$course_slug = ! empty( $settings['course_slug'] ) ? $settings['course_slug'] : 'courses';

		register_post_type(
			self::COURSE,
			array(
				'labels'          => $this->labels( __( 'Course', 'simple-lms' ), __( 'Courses', 'simple-lms' ) ),
				'public'          => true,
				'has_archive'     => true,
				'show_in_rest'    => true,
				'menu_icon'       => 'dashicons-welcome-learn-more',
				'menu_position'   => 26,
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'custom-fields' ),
				'rewrite'         => array( 'slug' => $course_slug ),
				'capability_type' => array( 'slms_course', 'slms_courses' ),
				'map_meta_cap'    => true,
			)
		);

		register_post_type(
			self::LESSON,
			array(
				'labels'          => $this->labels( __( 'Lesson', 'simple-lms' ), __( 'Lessons', 'simple-lms' ) ),
				'public'          => true,
				'has_archive'     => false,
				'show_in_rest'    => true,
				'show_in_menu'    => 'edit.php?post_type=' . self::COURSE,
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'page-attributes' ),
				'rewrite'         => array( 'slug' => 'lessons' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Build a standard label set.
	 *
	 * @param string $singular Singular label.
	 * @param string $plural   Plural label.
	 * @return array
	 */
	private function labels( $singular, $plural ) {
		return array(
			'name'               => $plural,
			'singular_name'      => $singular,
			'add_new'            => __( 'Add New', 'simple-lms' ),
			/* translators: %s: singular post type name */
			'add_new_item'       => sprintf( __( 'Add New %s', 'simple-lms' ), $singular ),
			/* translators: %s: singular post type name */
			'edit_item'          => sprintf( __( 'Edit %s', 'simple-lms' ), $singular ),
			/* translators: %s: singular post type name */
			'new_item'           => sprintf( __( 'New %s', 'simple-lms' ), $singular ),
			/* translators: %s: plural post type name */
			'view_items'         => sprintf( __( 'View %s', 'simple-lms' ), $plural ),
			/* translators: %s: plural post type name */
			'search_items'       => sprintf( __( 'Search %s', 'simple-lms' ), $plural ),
			/* translators: %s: plural post type name */
			'not_found'          => sprintf( __( 'No %s found', 'simple-lms' ), strtolower( $plural ) ),
			'menu_name'          => $plural,
		);
	}

	/**
	 * All post types registered by the plugin.
	 *
	 * @return string[]
	 */
	public static function all() {
		return array( self::COURSE, self::LESSON );
	}
}
