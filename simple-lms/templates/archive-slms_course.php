<?php
/**
 * Course archive.
 *
 * Copy to `yourtheme/simple-lms/archive-slms_course.php` to customise.
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="slms-wrap slms-archive">
	<header class="slms-archive__header">
		<h1 class="slms-archive__title">
			<?php
			if ( is_tax( SLMS_Post_Types::CAT_TAX ) ) {
				single_term_title();
			} else {
				post_type_archive_title();
			}
			?>
		</h1>
		<?php
		$description = is_tax( SLMS_Post_Types::CAT_TAX ) ? term_description() : '';
		if ( $description ) {
			echo '<div class="slms-archive__desc">' . wp_kses_post( $description ) . '</div>';
		}
		?>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="slms-grid slms-grid--cols-3">
			<?php
			while ( have_posts() ) :
				the_post();
				slms_get_template( 'parts/course-card.php', array( 'course_id' => get_the_ID() ) );
			endwhile;
			?>
		</div>

		<nav class="slms-pagination" aria-label="<?php esc_attr_e( 'Courses pagination', 'simple-lms' ); ?>">
			<?php
			the_posts_pagination(
				array(
					'prev_text' => __( '&larr; Previous', 'simple-lms' ),
					'next_text' => __( 'Next &rarr;', 'simple-lms' ),
				)
			);
			?>
		</nav>
	<?php else : ?>
		<p class="slms-empty"><?php esc_html_e( 'No courses found.', 'simple-lms' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
