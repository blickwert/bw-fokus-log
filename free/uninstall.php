<?php
/**
 * Removes all data when the plugin is deleted (not when it is deactivated).
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$bw_fokus_log_table = $wpdb->prefix . 'fokus_log_days';
$wpdb->query( "DROP TABLE IF EXISTS {$bw_fokus_log_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery

delete_option( 'bw_fokus_log_db_version' );
delete_option( 'bw_fokus_log_coach' );
delete_option( 'bw_fokus_log_modules' );
delete_metadata( 'user', 0, 'bw_fokus_log_pause', '', true );
