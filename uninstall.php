<?php
/**
 * Beim Löschen des Plugins (nicht beim Deaktivieren!) werden alle Daten entfernt.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$fokus_log_table = $wpdb->prefix . 'fokus_log_days';
$wpdb->query( "DROP TABLE IF EXISTS {$fokus_log_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

delete_option( 'fokus_log_settings' );
delete_option( 'fokus_log_db_version' );
delete_metadata( 'user', 0, 'fokus_log_pause', '', true );
