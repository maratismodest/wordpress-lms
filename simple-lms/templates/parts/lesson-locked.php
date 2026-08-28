<?php
/**
 * Shown in place of a locked lesson's content.
 *
 * @var int    $lesson_id Lesson ID.
 * @var int    $course_id Course ID.
 * @var string $excerpt   Trimmed teaser text.
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="slms-locked">
	<?php if ( ! empty( $excerpt ) ) : ?>
		<p class="slms-locked__teaser"><?php echo esc_html( $excerpt ); ?>…</p>
	<?php endif; ?>

	<div class="slms-locked__cta">
		<p><strong><?php esc_html_e( 'This lesson is for enrolled students.', 'simple-lms' ); ?></strong></p>
		<?php
		if ( ! empty( $course_id ) ) {
			echo do_shortcode( '[slms_enroll_button id="' . (int) $course_id . '"]' );
			printf(
				' <a class="slms-button" href="%s">%s</a>',
				esc_url( get_permalink( $course_id ) ),
				esc_html__( 'Course overview', 'simple-lms' )
			);
		}
		?>
	</div>
</div>
