<?php
/**
 * [slms_courses] output.
 *
 * @var WP_Query $query   Course query.
 * @var int      $columns Grid columns.
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

if ( ! $query->have_posts() ) {
	echo '<p class="slms-empty">' . esc_html__( 'No courses found.', 'simple-lms' ) . '</p>';
	wp_reset_postdata();
	return;
}
?>
<div class="slms-grid slms-grid--cols-<?php echo esc_attr( $columns ); ?>">
	<?php
	while ( $query->have_posts() ) :
		$query->the_post();
		slms_get_template( 'parts/course-card.php', array( 'course_id' => get_the_ID() ) );
	endwhile;
	?>
</div>

<?php
$big = 999999999;
$pagination = paginate_links(
	array(
		'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
		'format'    => '?paged=%#%',
		'current'   => max( 1, (int) $query->get( 'paged' ) ),
		'total'     => (int) $query->max_num_pages,
		'prev_text' => __( '&larr; Previous', 'simple-lms' ),
		'next_text' => __( 'Next &rarr;', 'simple-lms' ),
	)
);

if ( $pagination ) {
	echo '<nav class="slms-pagination" aria-label="' . esc_attr__( 'Courses pagination', 'simple-lms' ) . '">' . wp_kses_post( $pagination ) . '</nav>';
}

wp_reset_postdata();
