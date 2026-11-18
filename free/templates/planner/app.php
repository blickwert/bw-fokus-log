<?php
/**
 * Mount point of the Fokus-Log app. Override in your theme under
 * `bw-fokus-log/planner/app.php`.
 *
 * @package BW\FokusLog
 *
 * @var array{context: string} $data
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="bwfl-app" data-bwfl-context="<?php echo esc_attr( $data['context'] ); ?>">
	<p class="bwfl-loading"><?php esc_html_e( 'Loading Fokus-Log …', 'bw-fokus-log' ); ?></p>
	<?php
	/**
	 * Fires inside the mount point, before the app takes over.
	 */
	do_action( 'bw_fokus_log_app_mount' );
	?>
</div>
