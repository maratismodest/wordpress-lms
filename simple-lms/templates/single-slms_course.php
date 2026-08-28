<?php
/**
 * Single course.
 *
 * Copy to `yourtheme/simple-lms/single-slms_course.php` to customise.
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$course_id  = get_the_ID();
	$user_id    = get_current_user_id();
	$enrolled   = $user_id && slms_is_enrolled( $user_id, $course_id );
	$difficulty = get_post_meta( $course_id, '_slms_difficulty', true );
	$duration   = get_post_meta( $course_id, '_slms_duration', true );
	?>
	<article class="slms-wrap slms-course">
		<header class="slms-course__header">
			<h1 class="slms-course__title"><?php the_title(); ?></h1>

			<ul class="slms-course__meta">
				<?php if ( $difficulty ) : ?>
					<li><?php echo esc_html( ucfirst( $difficulty ) ); ?></li>
				<?php endif; ?>
				<?php if ( $duration ) : ?>
					<li><?php echo esc_html( $duration ); ?></li>
				<?php endif; ?>
				<li>
					<?php
					$n = count( slms_get_course_lessons( $course_id ) );
					printf(
						/* translators: %d: number of lessons */
						esc_html( _n( '%d lesson', '%d lessons', $n, 'simple-lms' ) ),
						(int) $n
					);
					?>
				</li>
			</ul>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<div class="slms-course__media"><?php the_post_thumbnail( 'large' ); ?></div>
		<?php endif; ?>

		<div class="slms-course__layout">
			<div class="slms-course__content">
				<?php the_content(); ?>
			</div>

			<aside class="slms-course__sidebar">
				<div class="slms-course__enroll">
					<?php echo do_shortcode( '[slms_enroll_button id="' . (int) $course_id . '"]' ); ?>
					<?php if ( $enrolled ) : ?>
						<?php echo do_shortcode( '[slms_course_progress id="' . (int) $course_id . '"]' ); ?>
					<?php endif; ?>
				</div>

				<h2 class="slms-course__curriculum-title"><?php esc_html_e( 'Curriculum', 'simple-lms' ); ?></h2>
				<?php echo do_shortcode( '[slms_lesson_list id="' . (int) $course_id . '"]' ); ?>
			</aside>
		</div>
	</article>
	<?php
endwhile;

get_footer();
