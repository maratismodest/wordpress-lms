<?php
/**
 * "Mark as complete" toggle for a single lesson.
 *
 * @var int $lesson_id Lesson ID.
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	return;
}

$user_id = get_current_user_id();

if ( ! slms_user_can_access_lesson( $user_id, $lesson_id ) ) {
	return;
}

$done = slms_is_lesson_complete( $user_id, $lesson_id );
?>
<div class="slms-lesson-actions">
	<button
		type="button"
		class="slms-button slms-complete<?php echo $done ? ' is-done' : ''; ?>"
		data-slms-complete="<?php echo esc_attr( $lesson_id ); ?>"
		aria-pressed="<?php echo $done ? 'true' : 'false'; ?>">
		<?php echo $done ? esc_html__( 'Completed', 'simple-lms' ) : esc_html__( 'Mark as complete', 'simple-lms' ); ?>
	</button>
	<span class="slms-complete__msg" role="status" aria-live="polite"></span>
</div>
