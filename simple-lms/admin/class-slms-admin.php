<?php
/**
 * Admin experience: settings screen, meta boxes, list-table columns.
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

class SLMS_Admin {

	const OPTION_GROUP = 'slms_settings_group';
	const OPTION_NAME  = 'slms_settings';
	const PAGE_SLUG    = 'slms-settings';

	/**
	 * Hook registration.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'maybe_activation_redirect' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . SLMS_Post_Types::LESSON, array( $this, 'save_lesson_meta' ), 10, 2 );
		add_action( 'save_post_' . SLMS_Post_Types::COURSE, array( $this, 'save_course_meta' ), 10, 2 );

		add_filter( 'manage_' . SLMS_Post_Types::LESSON . '_posts_columns', array( $this, 'lesson_columns' ) );
		add_action( 'manage_' . SLMS_Post_Types::LESSON . '_posts_custom_column', array( $this, 'lesson_column_content' ), 10, 2 );
		add_filter( 'manage_' . SLMS_Post_Types::COURSE . '_posts_columns', array( $this, 'course_columns' ) );
		add_action( 'manage_' . SLMS_Post_Types::COURSE . '_posts_custom_column', array( $this, 'course_column_content' ), 10, 2 );

		add_filter( 'plugin_action_links_' . SIMPLE_LMS_BASENAME, array( $this, 'action_links' ) );
	}

	/* --------------------------------------------------------------------- *
	 * Settings screen
	 * --------------------------------------------------------------------- */

	/**
	 * Add the "Settings" submenu under the Courses menu.
	 */
	public function add_settings_page() {
		add_submenu_page(
			'edit.php?post_type=' . SLMS_Post_Types::COURSE,
			__( 'Simple LMS Settings', 'simple-lms' ),
			__( 'Settings', 'simple-lms' ),
			'manage_slms',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register the option, section and fields via the Settings API.
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => SLMS_Activator::default_settings(),
			)
		);

		add_settings_section(
			'slms_main',
			__( 'General', 'simple-lms' ),
			'__return_false',
			self::PAGE_SLUG
		);

		$fields = array(
			'courses_per_page'         => __( 'Courses per page', 'simple-lms' ),
			'course_slug'              => __( 'Course URL slug', 'simple-lms' ),
			'require_login'            => __( 'Require login to view lessons', 'simple-lms' ),
			'auto_enroll_free'         => __( 'Auto-enroll on free courses', 'simple-lms' ),
			'dashboard_page_id'        => __( '“My Courses” page', 'simple-lms' ),
			'delete_data_on_uninstall' => __( 'Delete all data on uninstall', 'simple-lms' ),
		);

		foreach ( $fields as $key => $label ) {
			add_settings_field(
				$key,
				$label,
				array( $this, 'render_field' ),
				self::PAGE_SLUG,
				'slms_main',
				array( 'key' => $key )
			);
		}
	}

	/**
	 * Render a single settings field.
	 *
	 * @param array $args Field args ({ key }).
	 */
	public function render_field( $args ) {
		$key      = $args['key'];
		$settings = slms_get_settings();
		$value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
		$name     = self::OPTION_NAME . '[' . $key . ']';

		switch ( $key ) {
			case 'courses_per_page':
				printf(
					'<input type="number" min="1" max="100" name="%s" value="%s" class="small-text" />',
					esc_attr( $name ),
					esc_attr( $value )
				);
				break;

			case 'course_slug':
				printf(
					'<input type="text" name="%s" value="%s" class="regular-text" /><p class="description">%s</p>',
					esc_attr( $name ),
					esc_attr( $value ),
					esc_html__( 'Change requires re-saving Permalinks.', 'simple-lms' )
				);
				break;

			case 'dashboard_page_id':
				wp_dropdown_pages(
					array(
						'name'              => esc_attr( $name ),
						'selected'          => (int) $value,
						'show_option_none'  => __( '— none —', 'simple-lms' ),
						'option_none_value' => '0',
					)
				);
				break;

			case 'require_login':
			case 'auto_enroll_free':
			case 'delete_data_on_uninstall':
				printf(
					'<label><input type="checkbox" name="%s" value="1" %s /> %s</label>',
					esc_attr( $name ),
					checked( 1, (int) $value, false ),
					esc_html__( 'Enabled', 'simple-lms' )
				);
				break;
		}
	}

	/**
	 * Sanitize the whole settings array on save.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$input   = (array) $input;
		$current = slms_get_settings();

		return array(
			'courses_per_page'         => max( 1, min( 100, absint( $input['courses_per_page'] ?? $current['courses_per_page'] ) ) ),
			'course_slug'              => sanitize_title( $input['course_slug'] ?? $current['course_slug'] ) ?: 'courses',
			'require_login'            => empty( $input['require_login'] ) ? 0 : 1,
			'auto_enroll_free'         => empty( $input['auto_enroll_free'] ) ? 0 : 1,
			'dashboard_page_id'        => absint( $input['dashboard_page_id'] ?? 0 ),
			'delete_data_on_uninstall' => empty( $input['delete_data_on_uninstall'] ) ? 0 : 1,
		);
	}

	/**
	 * Output the settings page wrapper.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_slms' ) ) {
			return;
		}
		?>
		<div class="wrap slms-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
			<hr />
			<h2><?php esc_html_e( 'Shortcodes', 'simple-lms' ); ?></h2>
			<table class="widefat striped" style="max-width:760px">
				<tbody>
					<tr><td><code>[slms_courses]</code></td><td><?php esc_html_e( 'Grid of all courses. Attributes: category, per_page, columns, orderby, order.', 'simple-lms' ); ?></td></tr>
					<tr><td><code>[slms_my_courses]</code></td><td><?php esc_html_e( 'The logged-in user’s enrolled courses with progress bars.', 'simple-lms' ); ?></td></tr>
					<tr><td><code>[slms_course_progress id="ID"]</code></td><td><?php esc_html_e( 'Progress bar for one course (defaults to the current course/lesson).', 'simple-lms' ); ?></td></tr>
					<tr><td><code>[slms_enroll_button id="ID"]</code></td><td><?php esc_html_e( 'Enroll / continue button for a course.', 'simple-lms' ); ?></td></tr>
					<tr><td><code>[slms_lesson_list id="ID"]</code></td><td><?php esc_html_e( 'Course curriculum with completion ticks.', 'simple-lms' ); ?></td></tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * One-time redirect to the settings screen right after activation.
	 */
	public function maybe_activation_redirect() {
		if ( ! get_transient( 'slms_activation_redirect' ) ) {
			return;
		}
		delete_transient( 'slms_activation_redirect' );

		if ( wp_doing_ajax() || isset( $_GET['activate-multi'] ) || ! current_user_can( 'manage_slms' ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=' . SLMS_Post_Types::COURSE . '&page=' . self::PAGE_SLUG ) );
		exit;
	}

	/* --------------------------------------------------------------------- *
	 * Meta boxes
	 * --------------------------------------------------------------------- */

	/**
	 * Register meta boxes for courses and lessons.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'slms_lesson_details',
			__( 'Lesson Settings', 'simple-lms' ),
			array( $this, 'render_lesson_meta_box' ),
			SLMS_Post_Types::LESSON,
			'side',
			'high'
		);

		add_meta_box(
			'slms_course_details',
			__( 'Course Settings', 'simple-lms' ),
			array( $this, 'render_course_meta_box' ),
			SLMS_Post_Types::COURSE,
			'side',
			'default'
		);

		add_meta_box(
			'slms_course_curriculum',
			__( 'Curriculum', 'simple-lms' ),
			array( $this, 'render_curriculum_meta_box' ),
			SLMS_Post_Types::COURSE,
			'normal',
			'high'
		);
	}

	/**
	 * Lesson meta box: parent course, video URL, free preview.
	 *
	 * @param WP_Post $post Lesson.
	 */
	public function render_lesson_meta_box( $post ) {
		wp_nonce_field( 'slms_lesson_meta', 'slms_lesson_meta_nonce' );

		$course_id     = slms_get_lesson_course_id( $post->ID );
		$video_url     = get_post_meta( $post->ID, '_slms_video_url', true );
		$free_preview  = get_post_meta( $post->ID, '_slms_free_preview', true );

		$courses = get_posts(
			array(
				'post_type'   => SLMS_Post_Types::COURSE,
				'post_status' => array( 'publish', 'draft', 'pending', 'private' ),
				'numberposts' => -1,
				'orderby'     => 'title',
				'order'       => 'ASC',
			)
		);
		?>
		<p>
			<label for="slms_course_id"><strong><?php esc_html_e( 'Belongs to course', 'simple-lms' ); ?></strong></label><br />
			<select name="slms_course_id" id="slms_course_id" style="width:100%">
				<option value="0"><?php esc_html_e( '— select —', 'simple-lms' ); ?></option>
				<?php foreach ( $courses as $course ) : ?>
					<option value="<?php echo esc_attr( $course->ID ); ?>" <?php selected( $course_id, $course->ID ); ?>>
						<?php echo esc_html( $course->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="slms_video_url"><strong><?php esc_html_e( 'Video URL', 'simple-lms' ); ?></strong></label><br />
			<input type="url" name="slms_video_url" id="slms_video_url" value="<?php echo esc_attr( $video_url ); ?>" style="width:100%" placeholder="https://" />
		</p>
		<p>
			<label>
				<input type="checkbox" name="slms_free_preview" value="1" <?php checked( $free_preview, '1' ); ?> />
				<?php esc_html_e( 'Free preview (viewable without enrolling)', 'simple-lms' ); ?>
			</label>
		</p>
		<p class="description">
			<?php esc_html_e( 'Use the “Order” field in Page Attributes to sequence lessons.', 'simple-lms' ); ?>
		</p>
		<?php
	}

	/**
	 * Course meta box: difficulty, duration, price.
	 *
	 * @param WP_Post $post Course.
	 */
	public function render_course_meta_box( $post ) {
		wp_nonce_field( 'slms_course_meta', 'slms_course_meta_nonce' );

		$difficulty = get_post_meta( $post->ID, '_slms_difficulty', true );
		$duration   = get_post_meta( $post->ID, '_slms_duration', true );
		$price      = get_post_meta( $post->ID, '_slms_price', true );

		$levels = array(
			''             => __( '— none —', 'simple-lms' ),
			'beginner'     => __( 'Beginner', 'simple-lms' ),
			'intermediate' => __( 'Intermediate', 'simple-lms' ),
			'advanced'     => __( 'Advanced', 'simple-lms' ),
		);
		?>
		<p>
			<label for="slms_difficulty"><strong><?php esc_html_e( 'Difficulty', 'simple-lms' ); ?></strong></label><br />
			<select name="slms_difficulty" id="slms_difficulty" style="width:100%">
				<?php foreach ( $levels as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $difficulty, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="slms_duration"><strong><?php esc_html_e( 'Estimated duration', 'simple-lms' ); ?></strong></label><br />
			<input type="text" name="slms_duration" id="slms_duration" value="<?php echo esc_attr( $duration ); ?>" style="width:100%" placeholder="<?php esc_attr_e( 'e.g. 4 hours', 'simple-lms' ); ?>" />
		</p>
		<p>
			<label for="slms_price"><strong><?php esc_html_e( 'Price', 'simple-lms' ); ?></strong></label><br />
			<input type="number" min="0" step="0.01" name="slms_price" id="slms_price" value="<?php echo esc_attr( $price ); ?>" class="small-text" />
			<span class="description"><?php esc_html_e( '0 = free', 'simple-lms' ); ?></span>
		</p>
		<?php
	}

	/**
	 * Course meta box: read-only list of attached lessons + enrollment count.
	 *
	 * @param WP_Post $post Course.
	 */
	public function render_curriculum_meta_box( $post ) {
		$lessons = slms_get_course_lessons( $post->ID );
		$count   = slms_get_course_enrollment_count( $post->ID );

		echo '<p>' . sprintf(
			/* translators: %d: number of enrolled students */
			esc_html( _n( '%d student enrolled.', '%d students enrolled.', $count, 'simple-lms' ) ),
			(int) $count
		) . '</p>';

		if ( ! $lessons ) {
			echo '<p>' . esc_html__( 'No lessons yet. Create lessons and assign them to this course.', 'simple-lms' ) . '</p>';
		} else {
			echo '<ol class="slms-curriculum-list">';
			foreach ( $lessons as $lesson ) {
				printf(
					'<li><a href="%s">%s</a></li>',
					esc_url( get_edit_post_link( $lesson->ID ) ),
					esc_html( $lesson->post_title )
				);
			}
			echo '</ol>';
		}

		printf(
			'<p><a class="button" href="%s">%s</a></p>',
			esc_url( admin_url( 'post-new.php?post_type=' . SLMS_Post_Types::LESSON ) ),
			esc_html__( 'Add a lesson', 'simple-lms' )
		);
	}

	/**
	 * Persist lesson meta.
	 *
	 * @param int     $post_id Lesson ID.
	 * @param WP_Post $post    Lesson.
	 */
	public function save_lesson_meta( $post_id, $post ) {
		if ( ! isset( $_POST['slms_lesson_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['slms_lesson_meta_nonce'] ) ), 'slms_lesson_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$old_course = slms_get_lesson_course_id( $post_id );
		$new_course = isset( $_POST['slms_course_id'] ) ? absint( $_POST['slms_course_id'] ) : 0;

		update_post_meta( $post_id, '_slms_course_id', $new_course );
		update_post_meta( $post_id, '_slms_video_url', isset( $_POST['slms_video_url'] ) ? esc_url_raw( wp_unslash( $_POST['slms_video_url'] ) ) : '' );

		if ( ! empty( $_POST['slms_free_preview'] ) ) {
			update_post_meta( $post_id, '_slms_free_preview', '1' );
		} else {
			delete_post_meta( $post_id, '_slms_free_preview' );
		}

		foreach ( array_unique( array_filter( array( $old_course, $new_course ) ) ) as $course_id ) {
			wp_cache_delete( $course_id, 'slms_course_lessons' );
		}
	}

	/**
	 * Persist course meta.
	 *
	 * @param int     $post_id Course ID.
	 * @param WP_Post $post    Course.
	 */
	public function save_course_meta( $post_id, $post ) {
		if ( ! isset( $_POST['slms_course_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['slms_course_meta_nonce'] ) ), 'slms_course_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$allowed_levels = array( '', 'beginner', 'intermediate', 'advanced' );
		$difficulty     = isset( $_POST['slms_difficulty'] ) ? sanitize_key( wp_unslash( $_POST['slms_difficulty'] ) ) : '';
		if ( ! in_array( $difficulty, $allowed_levels, true ) ) {
			$difficulty = '';
		}

		update_post_meta( $post_id, '_slms_difficulty', $difficulty );
		update_post_meta( $post_id, '_slms_duration', isset( $_POST['slms_duration'] ) ? sanitize_text_field( wp_unslash( $_POST['slms_duration'] ) ) : '' );
		update_post_meta( $post_id, '_slms_price', isset( $_POST['slms_price'] ) ? max( 0, (float) wp_unslash( $_POST['slms_price'] ) ) : 0 );
	}

	/* --------------------------------------------------------------------- *
	 * List-table columns
	 * --------------------------------------------------------------------- */

	/**
	 * Add a "Course" column to the lessons list.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function lesson_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['slms_course']  = __( 'Course', 'simple-lms' );
				$new['slms_preview'] = __( 'Free preview', 'simple-lms' );
			}
		}
		return $new;
	}

	/**
	 * Render lesson column cells.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Lesson ID.
	 */
	public function lesson_column_content( $column, $post_id ) {
		if ( 'slms_course' === $column ) {
			$course_id = slms_get_lesson_course_id( $post_id );
			if ( $course_id ) {
				printf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $course_id ) ), esc_html( get_the_title( $course_id ) ) );
			} else {
				echo '<span aria-hidden="true">—</span>';
			}
		}

		if ( 'slms_preview' === $column ) {
			echo get_post_meta( $post_id, '_slms_free_preview', true ) ? '✓' : '<span aria-hidden="true">—</span>';
		}
	}

	/**
	 * Add "Lessons" and "Students" columns to the courses list.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function course_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['slms_lessons']  = __( 'Lessons', 'simple-lms' );
				$new['slms_students'] = __( 'Students', 'simple-lms' );
			}
		}
		return $new;
	}

	/**
	 * Render course column cells.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Course ID.
	 */
	public function course_column_content( $column, $post_id ) {
		if ( 'slms_lessons' === $column ) {
			echo (int) count( slms_get_course_lessons( $post_id ) );
		}
		if ( 'slms_students' === $column ) {
			echo (int) slms_get_course_enrollment_count( $post_id );
		}
	}

	/**
	 * Add a "Settings" link on the Plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function action_links( $links ) {
		$url = admin_url( 'edit.php?post_type=' . SLMS_Post_Types::COURSE . '&page=' . self::PAGE_SLUG );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'simple-lms' ) . '</a>' );
		return $links;
	}
}
