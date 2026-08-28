<?php
/**
 * Course curriculum list.
 *
 * @var int       $course_id Course ID.
 * @var WP_Post[] $lessons   Ordered lessons.
 * @var int       $user_id   Current user ID (0 for guests).
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $lessons ) ) {
	echo '<p class="slms-empty">' . esc_html__( 'No lessons in this course yet.', 'simple-lms' ) . '</p>';
	return;
}

$current_id = get_the_ID();
?>
<ol class="slms-lesson-list">
	<?php foreach ( $lessons as $index => $lesson ) : ?>
		<?php
		$done    = $user_id && slms_is_lesson_complete( $user_id, $lesson->ID );
		$access  = slms_user_can_access_lesson( $user_id, $lesson->ID );
		$preview = (bool) get_post_meta( $lesson->ID, '_slms_free_preview', true );
		$classes = array( 'slms-lesson-list__item' );
		if ( $done ) {
			$classes[] = 'is-done';
		}
		if ( $lesson->ID === $current_id ) {
			$classes[] = 'is-current';
		}
		if ( ! $access ) {
			$classes[] = 'is-locked';
		}
		?>
		<li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<span class="slms-lesson-list__status" aria-hidden="true"><?php echo $done ? '✓' : (string) ( $index + 1 ); ?></span>
			<?php if ( $access ) : ?>
				<a class="slms-lesson-list__link" href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>">
					<?php echo esc_html( get_the_title( $lesson->ID ) ); ?>
				</a>
			<?php else : ?>
				<span class="slms-lesson-list__link"><?php echo esc_html( get_the_title( $lesson->ID ) ); ?></span>
			<?php endif; ?>
			<?php if ( $preview ) : ?>
				<span class="slms-badge slms-badge--preview"><?php esc_html_e( 'Preview', 'simple-lms' ); ?></span>
			<?php elseif ( ! $access ) : ?>
				<span class="slms-badge slms-badge--locked" aria-label="<?php esc_attr_e( 'Locked', 'simple-lms' ); ?>">🔒</span>
			<?php endif; ?>
			<?php
			if ( $done ) {
				echo '<span class="screen-reader-text">' . esc_html__( 'Completed', 'simple-lms' ) . '</span>';
			}
			?>
		</li>
	<?php endforeach; ?>
</ol>
