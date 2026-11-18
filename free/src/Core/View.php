<?php
/**
 * Template loader with theme overrides.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Finds and renders PHP templates. A theme may override each one
 * under `<theme>/bw-fokus-log/<area>/<name>.php`.
 */
final class View {

	/**
	 * Find the template file, theme first.
	 *
	 * @param string $template Path below templates/, without extension, e.g. `planner/app`.
	 */
	public static function locate( string $template ): string {
		$relative = $template . '.php';
		$theme    = locate_template( 'bw-fokus-log/' . $relative );
		$file     = '' !== $theme ? $theme : BW_FOKUS_LOG_DIR . 'templates/' . $relative;

		/**
		 * Lets a site use another template file.
		 *
		 * @param string $file     Absolute path.
		 * @param string $template Template name.
		 */
		return (string) apply_filters( 'bw_fokus_log_template', $file, $template );
	}

	/**
	 * Render a template and return the HTML.
	 *
	 * @param string               $template Template name.
	 * @param array<string, mixed> $data     Variables for the template.
	 */
	public static function render( string $template, array $data = array() ): string {
		$file = self::locate( $template );

		if ( ! is_readable( $file ) ) {
			return '';
		}

		ob_start();
		( static function ( string $bw_file, array $bw_data ): void {
			// Variables are passed as one array; templates read $data.
			$data = $bw_data;
			include $bw_file;
		} )( $file, $data );

		return (string) ob_get_clean();
	}
}
