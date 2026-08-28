<?php
/**
 * [slms_my_courses] output.
 *
 * @var int   $user_id     Current user ID.
 * @var array $enrollments Rows from slms_get_user_courses().
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $enrollments ) ) {
	echo '<p class="slms-empty">' . esc_html__( 'You are not enrolled in any courses yet.', 'simple-lms' ) . '</p>';
	return;
}
?>
<ul class="slms-my-courses">
	<?php foreach ( $enrollments as $row ) : ?>
		<?php
		$course_id = (int) $row['course_id'];
		if ( get_post_type( $course_id ) !== SLMS_Post_Types::COURSE || 'publish' !== get_post_status( $course_id ) ) {
			continue;
		}
		$progress = slms_get_course_progress( $user_id, $course_id );
		$lessons  = slms_get_course_lessons( $course_id );
		$resume   = $lessons ? $lessons[0] : null;
		foreach ( $lessons as $lesson ) {
			if ( ! slms_is_lesson_complete( $user_id, $lesson->ID ) ) {
				$resume = $lesson;
				break;
			}
		}
		?>
		<li class="slms-my-courses__item<?php echo $progress['complete'] ? ' is-complete' : ''; ?>">
			<div class="slms-my-courses__head">
				<h3 class="slms-my-courses__title">
					<a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>"><?php echo esc_html( get_the_title( $course_id ) ); ?></a>
				</h3>
				<?php if ( ! empty( $row['completed_at'] ) ) : ?>
					<span class="slms-badge slms-badge--done"><?php esc_html_e( 'Completed', 'simple-lms' ); ?></span>
				<?php endif; ?>
			</div>

			<?php slms_get_template( 'parts/progress-bar.php', array( 'progress' => $progress ) ); ?>

			<?php if ( $resume && ! $progress['complete'] ) : ?>
				<a class="slms-button slms-button--primary" href="<?php echo esc_url( get_permalink( $resume->ID ) ); ?>">
					<?php esc_html_e( 'Resume', 'simple-lms' ); ?>
				</a>
			<?php else : ?>
				<a class="slms-button" href="<?php echo esc_url( get_permalink( $course_id ) ); ?>">
					<?php esc_html_e( 'Review', 'simple-lms' ); ?>
				</a>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
