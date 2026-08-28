<?php
/**
 * Enroll / continue button part.
 *
 * @var int  $course_id Course ID.
 * @var bool $enrolled  Whether the current user is enrolled.
 * @var bool $is_free   Whether the course is free.
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

if ( $enrolled ) {
	$lessons = slms_get_course_lessons( $course_id );
	$first   = $lessons ? $lessons[0] : null;
	?>
	<a class="slms-button slms-button--primary slms-enroll slms-enroll--done"
		href="<?php echo esc_url( $first ? get_permalink( $first->ID ) : get_permalink( $course_id ) ); ?>">
		<?php esc_html_e( 'Continue', 'simple-lms' ); ?>
	</a>
	<?php
	return;
}

if ( ! is_user_logged_in() ) {
	?>
	<a class="slms-button slms-button--primary" href="<?php echo esc_url( wp_login_url( get_permalink( $course_id ) ) ); ?>">
		<?php esc_html_e( 'Log in to enroll', 'simple-lms' ); ?>
	</a>
	<?php
	return;
}

if ( ! $is_free ) {
	?>
	<button class="slms-button" type="button" disabled>
		<?php esc_html_e( 'Enrollment closed', 'simple-lms' ); ?>
	</button>
	<?php
	return;
}
?>
<button class="slms-button slms-button--primary slms-enroll" type="button" data-slms-enroll="<?php echo esc_attr( $course_id ); ?>">
	<?php esc_html_e( 'Enroll for free', 'simple-lms' ); ?>
</button>
<span class="slms-enroll__msg" role="status" aria-live="polite"></span>
