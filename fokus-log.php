<?php
/**
 * Plugin Name:       Fokus-Log
 * Description:       Tagesplan als einfache Textliste („- To-do“), Timer pro To-do, feste Pausen zwischen den Aufgaben, motivierender Coach und Leistungsverlauf. Shortcode: [fokus_log]
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            David
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fokus-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FOKUS_LOG_VERSION', '1.0.0' );
define( 'FOKUS_LOG_FILE', __FILE__ );
define( 'FOKUS_LOG_DIR', plugin_dir_path( __FILE__ ) );
define( 'FOKUS_LOG_URL', plugin_dir_url( __FILE__ ) );

require_once FOKUS_LOG_DIR . 'includes/class-fokus-log-store.php';
require_once FOKUS_LOG_DIR . 'includes/class-fokus-log-settings.php';
require_once FOKUS_LOG_DIR . 'includes/class-fokus-log-rest.php';
require_once FOKUS_LOG_DIR . 'includes/class-fokus-log-app.php';

register_activation_hook( __FILE__, array( 'Fokus_Log_Store', 'install' ) );

add_action( 'plugins_loaded', array( 'Fokus_Log_Store', 'maybe_upgrade' ) );
add_action( 'rest_api_init', array( 'Fokus_Log_Rest', 'register' ) );

Fokus_Log_Settings::init();
Fokus_Log_App::init();
