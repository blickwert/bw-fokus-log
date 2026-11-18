<?php
/**
 * Plugin Name:       BW Fokus-Log
 * Description:       Daily planner against procrastination: plan as a text list, a timer per to-do, fixed breaks, a motivating coach and a progress history.
 * Version:           2.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.3
 * Author:            Blickwert
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bw-fokus-log
 * Domain Path:       /languages
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BW_FOKUS_LOG_VERSION', '2.0.0' );
define( 'BW_FOKUS_LOG_FILE', __FILE__ );
define( 'BW_FOKUS_LOG_DIR', plugin_dir_path( __FILE__ ) );
define( 'BW_FOKUS_LOG_URL', plugin_dir_url( __FILE__ ) );

$bw_fokus_log_autoload = BW_FOKUS_LOG_DIR . 'vendor/autoload.php';

if ( ! is_readable( $bw_fokus_log_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p>'
				. esc_html__( 'BW Fokus-Log: the Composer autoloader is missing. Install the release ZIP or run "composer install".', 'bw-fokus-log' )
				. '</p></div>';
		}
	);
	return;
}

require_once $bw_fokus_log_autoload;

register_activation_hook( __FILE__, array( \BW\FokusLog\Core\Plugin::class, 'activate' ) );

\BW\FokusLog\Core\Plugin::instance()->boot();
