<?php
/**
 * Shown by shortcodes that need an authenticated user.
 *
 * @package SimpleLMS
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="slms-notice slms-notice--login">
	<p>
		<?php
		printf(
			/* translators: %s: login URL */
			wp_kses_post( __( 'Please <a href="%s">log in</a> to see your courses.', 'simple-lms' ) ),
			esc_url( wp_login_url( get_permalink() ) )
		);
		?>
	</p>
</div>
