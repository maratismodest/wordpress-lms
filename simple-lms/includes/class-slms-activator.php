<?php
/**
 * Runs on plugin activation: creates custom tables, registers CPTs, flushes rewrites.
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

class SLMS_Activator {

	/**
	 * Option key holding the installed DB schema version.
	 */
	const DB_VERSION_OPTION = 'slms_db_version';

	/**
	 * Current DB schema version. Bump when table structure changes.
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Activation entry point.
	 */
	public static function activate() {
		self::create_tables();
		self::add_capabilities();

		// Ensure CPT rules exist before flushing.
		( new SLMS_Post_Types() )->register_post_types();
		flush_rewrite_rules();

		if ( false === get_option( 'slms_settings' ) ) {
			add_option( 'slms_settings', self::default_settings() );
		}

		set_transient( 'slms_activation_redirect', 1, 30 );
	}

	/**
	 * Default plugin settings.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return array(
			'courses_per_page'         => 12,
			'require_login'            => 1,
			'auto_enroll_free'         => 1,
			'course_slug'              => 'courses',
			'dashboard_page_id'        => 0,
			'delete_data_on_uninstall' => 0,
		);
	}

	/**
	 * Create / upgrade custom database tables.
	 */
	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$enrollments     = $wpdb->prefix . 'slms_enrollments';
		$progress        = $wpdb->prefix . 'slms_progress';

		$sql = array();

		$sql[] = "CREATE TABLE {$enrollments} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			course_id BIGINT(20) UNSIGNED NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			enrolled_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			completed_at DATETIME DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_course (user_id, course_id),
			KEY course_id (course_id),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$progress} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			course_id BIGINT(20) UNSIGNED NOT NULL,
			lesson_id BIGINT(20) UNSIGNED NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'complete',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY user_lesson (user_id, lesson_id),
			KEY course_id (course_id),
			KEY user_course (user_id, course_id)
		) {$charset_collate};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Grant course-management caps to administrators and editors.
	 */
	public static function add_capabilities() {
		$caps = array(
			'edit_slms_course',
			'read_slms_course',
			'delete_slms_course',
			'edit_slms_courses',
			'edit_others_slms_courses',
			'publish_slms_courses',
			'read_private_slms_courses',
			'delete_slms_courses',
			'manage_slms',
		);

		foreach ( array( 'administrator', 'editor' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( $caps as $cap ) {
				if ( 'editor' === $role_name && 'manage_slms' === $cap ) {
					continue;
				}
				$role->add_cap( $cap );
			}
		}
	}

	/**
	 * Run table upgrades when the stored DB version is behind.
	 */
	public static function maybe_upgrade() {
		if ( get_option( self::DB_VERSION_OPTION ) !== self::DB_VERSION ) {
			self::create_tables();
		}
	}
}
