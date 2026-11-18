<?php
/**
 * Loads the browser app and renders its mount point.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the built script and style once and prints the container
 * that the app mounts into. Used by the admin page, the block and the shortcode.
 */
final class App {

	public const HANDLE = 'bw-fokus-log-app';

	/**
	 * Mount point HTML plus the assets it needs.
	 *
	 * @param string $context Where it is shown: `admin` or `site`.
	 */
	public static function mount( string $context ): string {
		if ( ! self::enqueue( $context ) ) {
			return '<p>' . esc_html__( 'The Fokus-Log app has not been built yet. Run "npm run build".', 'bw-fokus-log' ) . '</p>';
		}

		return View::render( 'planner/app', array( 'context' => $context ) );
	}

	/**
	 * Register script, style and data. Returns false when there is no build.
	 *
	 * @param string $context Where it is shown: `admin` or `site`.
	 */
	public static function enqueue( string $context ): bool {
		$asset_file = BW_FOKUS_LOG_DIR . 'build/index.asset.php';

		if ( ! is_readable( $asset_file ) ) {
			return false;
		}

		/**
		 * Build manifest written by @wordpress/scripts.
		 *
		 * @var array{dependencies: string[], version: string} $asset
		 */
		$asset = require $asset_file;

		wp_enqueue_script( self::HANDLE, BW_FOKUS_LOG_URL . 'build/index.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( self::HANDLE, 'bw-fokus-log', BW_FOKUS_LOG_DIR . 'languages' );

		$style = is_readable( BW_FOKUS_LOG_DIR . 'build/style-index.css' ) ? 'style-index.css' : 'index.css';
		wp_enqueue_style( self::HANDLE, BW_FOKUS_LOG_URL . 'build/' . $style, array(), $asset['version'] );

		$config = array(
			'context' => $context,
			'userId'  => get_current_user_id(),
			'name'    => wp_get_current_user()->display_name,
			'locale'  => str_replace( '_', '-', get_user_locale() ),
			'restNs'  => '/bw-fokus-log/v1',
			'canEdit' => current_user_can( 'manage_options' ),
		);

		/**
		 * Lets modules add data for the browser app.
		 *
		 * @param array<string, mixed> $config  Data so far.
		 * @param string               $context `admin` or `site`.
		 */
		$config = apply_filters( 'bw_fokus_log_app_config', $config, $context );

		wp_add_inline_script( self::HANDLE, 'window.bwFokusLog = ' . wp_json_encode( $config ) . ';', 'before' );

		return true;
	}
}
