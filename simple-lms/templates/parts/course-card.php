<?php
/**
 * Single course card (used inside the [slms_courses] grid).
 *
 * @var int $course_id Course ID (falls back to the current loop post).
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

$course_id  = ! empty( $course_id ) ? (int) $course_id : get_the_ID();
$difficulty = get_post_meta( $course_id, '_slms_difficulty', true );
$duration   = get_post_meta( $course_id, '_slms_duration', true );
$lesson_n   = count( slms_get_course_lessons( $course_id ) );
$is_free    = slms_course_is_free( $course_id );
$enrolled   = is_user_logged_in() && slms_is_enrolled( get_current_user_id(), $course_id );
?>
<article class="slms-card">
	<?php if ( has_post_thumbnail( $course_id ) ) : ?>
		<a class="slms-card__media" href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo get_the_post_thumbnail( $course_id, 'medium_large' ); ?>
		</a>
	<?php endif; ?>

	<div class="slms-card__body">
		<h3 class="slms-card__title">
			<a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>"><?php echo esc_html( get_the_title( $course_id ) ); ?></a>
		</h3>

		<p class="slms-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $course_id ), 22 ) ); ?></p>

		<ul class="slms-card__meta">
			<li>
				<?php
				printf(
					/* translators: %d: number of lessons */
					esc_html( _n( '%d lesson', '%d lessons', $lesson_n, 'simple-lms' ) ),
					(int) $lesson_n
				);
				?>
			</li>
			<?php if ( $difficulty ) : ?>
				<li class="slms-card__level"><?php echo esc_html( ucfirst( $difficulty ) ); ?></li>
			<?php endif; ?>
			<?php if ( $duration ) : ?>
				<li><?php echo esc_html( $duration ); ?></li>
			<?php endif; ?>
			<li class="slms-card__price"><?php echo $is_free ? esc_html__( 'Free', 'simple-lms' ) : esc_html( get_post_meta( $course_id, '_slms_price', true ) ); ?></li>
		</ul>

		<a class="slms-button slms-button--primary" href="<?php echo esc_url( get_permalink( $course_id ) ); ?>">
			<?php echo $enrolled ? esc_html__( 'Continue', 'simple-lms' ) : esc_html__( 'View course', 'simple-lms' ); ?>
		</a>
	</div>
</article>
