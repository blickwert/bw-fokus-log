<?php
/**
 * Storage of the days.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Modules\Planner\Repository;

use BW\FokusLog\Modules\Planner\Model\Day;

defined( 'ABSPATH' ) || exit;

// This class owns a custom table, so direct queries are the intended way; a cache would only hide the live timer saves.
// phpcs:disable WordPress.DB.DirectDatabaseQuery

/**
 * One row per user and day in `{prefix}fokus_log_days`.
 */
class DayRepository {

	public const DB_VERSION        = '1';
	public const OPTION_DB_VERSION = 'bw_fokus_log_db_version';

	/**
	 * Full table name. Kept from the first plugin version so existing data stays.
	 */
	public function table(): string {
		global $wpdb;

		return $wpdb->prefix . 'fokus_log_days';
	}

	/**
	 * Create or update the table.
	 */
	public function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = $this->table();
		$charset = $wpdb->get_charset_collate();

		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL,
				day date NOT NULL,
				content longtext NOT NULL,
				total_count smallint(5) unsigned NOT NULL DEFAULT 0,
				done_count smallint(5) unsigned NOT NULL DEFAULT 0,
				focus_seconds int(10) unsigned NOT NULL DEFAULT 0,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY user_day (user_id,day)
			) {$charset};"
		);

		update_option( self::OPTION_DB_VERSION, self::DB_VERSION );
	}

	/**
	 * Run the installer when the stored schema is outdated.
	 */
	public function maybe_upgrade(): void {
		if ( get_option( self::OPTION_DB_VERSION ) !== self::DB_VERSION ) {
			$this->install();
		}
	}

	/**
	 * Load one day, or null when nothing was saved.
	 *
	 * @param int    $user_id Owner.
	 * @param string $date    `YYYY-MM-DD`.
	 */
	public function find( int $user_id, string $date ): ?Day {
		global $wpdb;

		$table = $this->table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT content, updated_at FROM {$table} WHERE user_id = %d AND day = %s", $user_id, $date ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return is_array( $row ) ? new Day( $user_id, $date, (string) $row['content'], (string) $row['updated_at'] ) : null;
	}

	/**
	 * Insert or update a day together with its precomputed numbers.
	 *
	 * @param Day                                        $day   Day to store.
	 * @param array{total: int, done: int, seconds: int} $stats Numbers derived from the text.
	 */
	public function save( Day $day, array $stats ): void {
		global $wpdb;

		$table = $this->table();
		$data  = array(
			'user_id'       => $day->user_id,
			'day'           => $day->date,
			'content'       => $day->content,
			'total_count'   => min( 65535, $stats['total'] ),
			'done_count'    => min( 65535, $stats['done'] ),
			'focus_seconds' => $stats['seconds'],
			'updated_at'    => current_time( 'mysql' ),
		);
		$fmt   = array( '%d', '%s', '%s', '%d', '%d', '%d', '%s' );

		$existing = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE user_id = %d AND day = %s", $day->user_id, $day->date ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		if ( $existing ) {
			$wpdb->update( $table, $data, array( 'id' => (int) $existing ), $fmt, array( '%d' ) );
			return;
		}

		$wpdb->insert( $table, $data, $fmt );
	}

	/**
	 * Numbers per day between two days (inclusive), keyed by day.
	 *
	 * @param int    $user_id Owner.
	 * @param string $from    First day.
	 * @param string $to      Last day.
	 * @return array<string, array{total: int, done: int, seconds: int}>
	 */
	public function stats_between( int $user_id, string $from, string $to ): array {
		global $wpdb;

		$table = $this->table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT day, total_count, done_count, focus_seconds FROM {$table} WHERE user_id = %d AND day BETWEEN %s AND %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$from,
				$to
			),
			ARRAY_A
		);

		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[ (string) $row['day'] ] = array(
				'total'   => (int) $row['total_count'],
				'done'    => (int) $row['done_count'],
				'seconds' => (int) $row['focus_seconds'],
			);
		}

		return $out;
	}

	/**
	 * Delete the table (used by uninstall.php through the same name).
	 */
	public function drop(): void {
		global $wpdb;

		$table = $this->table();
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
