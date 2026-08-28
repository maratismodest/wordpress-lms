<?php
/**
 * Previous / next lesson navigation.
 *
 * @var int $lesson_id Current lesson ID.
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

$prev = slms_get_adjacent_lesson( $lesson_id, 'prev' );
$next = slms_get_adjacent_lesson( $lesson_id, 'next' );

if ( ! $prev && ! $next ) {
	return;
}
?>
<nav class="slms-lesson-nav" aria-label="<?php esc_attr_e( 'Lesson navigation', 'simple-lms' ); ?>">
	<div class="slms-lesson-nav__prev">
		<?php if ( $prev ) : ?>
			<a rel="prev" href="<?php echo esc_url( get_permalink( $prev->ID ) ); ?>">
				<span class="slms-lesson-nav__dir">&larr; <?php esc_html_e( 'Previous', 'simple-lms' ); ?></span>
				<span class="slms-lesson-nav__title"><?php echo esc_html( get_the_title( $prev->ID ) ); ?></span>
			</a>
		<?php endif; ?>
	</div>
	<div class="slms-lesson-nav__next">
		<?php if ( $next ) : ?>
			<a rel="next" href="<?php echo esc_url( get_permalink( $next->ID ) ); ?>">
				<span class="slms-lesson-nav__dir"><?php esc_html_e( 'Next', 'simple-lms' ); ?> &rarr;</span>
				<span class="slms-lesson-nav__title"><?php echo esc_html( get_the_title( $next->ID ) ); ?></span>
			</a>
		<?php endif; ?>
	</div>
</nav>
