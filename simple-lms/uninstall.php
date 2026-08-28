<?php
/**
 * Uninstall routine.
 *
 * Only removes data when the site owner opted in via
 * Settings → "Delete all data on uninstall". Otherwise this is a no-op so a
 * re-install keeps every course, enrollment and progress record.
 *
 * @package SimpleLMS
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$settings = get_option( 'slms_settings', array() );

if ( empty( $settings['delete_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

// 1. Drop custom tables.
$tables = array(
	$wpdb->prefix . 'slms_enrollments',
	$wpdb->prefix . 'slms_progress',
);
foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
}

// 2. Delete all course + lesson posts (and their meta / term relationships).
$post_ids = get_posts(
	array(
		'post_type'   => array( 'slms_course', 'slms_lesson' ),
		'post_status' => 'any',
		'numberposts' => -1,
		'fields'      => 'ids',
	)
);
foreach ( $post_ids as $post_id ) {
	wp_delete_post( $post_id, true );
}

// 3. Delete course-category terms.
$term_ids = get_terms(
	array(
		'taxonomy'   => 'slms_course_cat',
		'hide_empty' => false,
		'fields'     => 'ids',
	)
);
if ( ! is_wp_error( $term_ids ) ) {
	foreach ( $term_ids as $term_id ) {
		wp_delete_term( $term_id, 'slms_course_cat' );
	}
}

// 4. Delete options / transients.
delete_option( 'slms_settings' );
delete_option( 'slms_db_version' );
delete_transient( 'slms_activation_redirect' );

// 5. Strip custom capabilities from every role.
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
foreach ( wp_roles()->roles as $role_name => $role_info ) {
	$role = get_role( $role_name );
	if ( ! $role ) {
		continue;
	}
	foreach ( $caps as $cap ) {
		$role->remove_cap( $cap );
	}
}

// 6. Clean up user-meta / object cache groups (best effort).
wp_cache_flush();
