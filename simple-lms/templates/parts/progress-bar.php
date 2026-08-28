<?php
/**
 * Progress bar part.
 *
 * @var array $progress { percent, completed, total, complete }
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;

$percent   = isset( $progress['percent'] ) ? (int) $progress['percent'] : 0;
$completed = isset( $progress['completed'] ) ? (int) $progress['completed'] : 0;
$total     = isset( $progress['total'] ) ? (int) $progress['total'] : 0;
?>
<div class="slms-progress" data-slms-progress>
	<div class="slms-progress__track" role="progressbar" aria-valuenow="<?php echo esc_attr( $percent ); ?>" aria-valuemin="0" aria-valuemax="100">
		<span class="slms-progress__fill" style="width:<?php echo esc_attr( $percent ); ?>%"></span>
	</div>
	<p class="slms-progress__label">
		<?php
		printf(
			/* translators: 1: completed lesson count, 2: total lesson count, 3: percentage */
			esc_html__( '%1$d / %2$d lessons (%3$d%%)', 'simple-lms' ),
			$completed,
			$total,
			$percent
		);
		?>
	</p>
</div>
