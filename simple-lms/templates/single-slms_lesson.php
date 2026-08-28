<?php
/**
 * Single lesson.
 *
 * Copy to `yourtheme/simple-lms/single-slms_lesson.php` to customise.
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$lesson_id = get_the_ID();
	$course_id = slms_get_lesson_course_id( $lesson_id );
	$user_id   = get_current_user_id();
	$has_access = slms_user_can_access_lesson( $user_id, $lesson_id );
	$video_url = get_post_meta( $lesson_id, '_slms_video_url', true );
	?>
	<article class="slms-wrap slms-lesson">
		<div class="slms-lesson__main">
			<?php if ( $course_id ) : ?>
				<p class="slms-lesson__breadcrumb">
					<a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>">&larr; <?php echo esc_html( get_the_title( $course_id ) ); ?></a>
				</p>
			<?php endif; ?>

			<h1 class="slms-lesson__title"><?php the_title(); ?></h1>

			<?php if ( $has_access && $video_url ) : ?>
				<div class="slms-lesson__video">
					<?php echo wp_kses_post( wp_oembed_get( $video_url ) ? wp_oembed_get( $video_url ) : '' ); ?>
				</div>
			<?php endif; ?>

			<div class="slms-lesson__content">
				<?php
				// the_content filters append the completion control + lesson nav
				// (see SLMS_Progress::append_lesson_controls).
				the_content();
				?>
			</div>
		</div>

		<?php if ( $course_id ) : ?>
			<aside class="slms-lesson__sidebar">
				<?php if ( $user_id ) : ?>
					<?php echo do_shortcode( '[slms_course_progress id="' . (int) $course_id . '"]' ); ?>
				<?php endif; ?>
				<h2 class="slms-lesson__curriculum-title"><?php esc_html_e( 'Lessons', 'simple-lms' ); ?></h2>
				<?php echo do_shortcode( '[slms_lesson_list id="' . (int) $course_id . '"]' ); ?>
			</aside>
		<?php endif; ?>
	</article>
	<?php
endwhile;

get_footer();
