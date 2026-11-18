<?php
/**
 * Speicherung: eine Zeile pro Benutzer und Tag.
 * Der Text im Format „- To-do {start|ende|dauer}“ ist die Quelle der Wahrheit,
 * die Zahlen (erledigt, Fokuszeit) werden beim Speichern daraus berechnet –
 * damit der Verlauf schnell geladen werden kann.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Fokus_Log_Store {

	const DB_VERSION = '1';
	const MAX_LENGTH = 100000;

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'fokus_log_days';
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
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
		) {$charset};";

		dbDelta( $sql );
		update_option( 'fokus_log_db_version', self::DB_VERSION );
	}

	public static function maybe_upgrade() {
		if ( get_option( 'fokus_log_db_version' ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	/**
	 * Zerlegt den Tagesplan in Einträge.
	 * Erkennt „- Titel“ und „- Titel {09:05|09:31|00:26}“.
	 */
	public static function parse( $text ) {
		$items = array();
		$lines = preg_split( '/\r\n|\r|\n/', (string) $text );

		foreach ( $lines as $line ) {
			if ( ! preg_match( '/^\s*[-•*]\s*(.+?)\s*$/u', $line, $m ) ) {
				continue;
			}
			$item = array(
				'title'   => $m[1],
				'start'   => '',
				'end'     => '',
				'seconds' => 0,
				'done'    => false,
			);
			if ( preg_match( '/^(.*?)\s*\{([^|}]*)\|([^|}]*)\|([^}]*)\}\s*$/u', $m[1], $b ) ) {
				$item['title']   = trim( $b[1] );
				$item['start']   = trim( $b[2] );
				$item['end']     = trim( $b[3] );
				$item['seconds'] = self::parse_duration( $b[4] );
				$item['done']    = '' !== $item['end'];
			}
			$items[] = $item;
		}
		return $items;
	}

	public static function parse_duration( $value ) {
		$parts = array_map( 'intval', explode( ':', trim( (string) $value ) ) );
		if ( 3 === count( $parts ) ) {
			return $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
		}
		if ( 2 === count( $parts ) ) {
			return $parts[0] * 3600 + $parts[1] * 60;
		}
		return 0;
	}

	public static function get_day( $user_id, $day ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT content, updated_at FROM {$table} WHERE user_id = %d AND day = %s", $user_id, $day ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
	}

	public static function save_day( $user_id, $day, $content ) {
		global $wpdb;
		$table = self::table();
		$items = self::parse( $content );
		$done  = 0;
		$secs  = 0;
		foreach ( $items as $item ) {
			if ( $item['done'] ) {
				$done++;
			}
			$secs += $item['seconds'];
		}

		$data    = array(
			'user_id'       => (int) $user_id,
			'day'           => $day,
			'content'       => $content,
			'total_count'   => min( 65535, count( $items ) ),
			'done_count'    => min( 65535, $done ),
			'focus_seconds' => $secs,
			'updated_at'    => current_time( 'mysql' ),
		);
		$formats = array( '%d', '%s', '%s', '%d', '%d', '%d', '%s' );

		$existing = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE user_id = %d AND day = %s", $user_id, $day ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		if ( $existing ) {
			$wpdb->update( $table, $data, array( 'id' => (int) $existing ), $formats, array( '%d' ) );
		} else {
			$wpdb->insert( $table, $data, $formats );
		}

		return array(
			'total'   => count( $items ),
			'done'    => $done,
			'seconds' => $secs,
		);
	}

	/**
	 * Liefert für jeden Tag im Zeitraum einen Eintrag (fehlende Tage = 0).
	 */
	public static function history( $user_id, $today, $days ) {
		global $wpdb;
		$table = self::table();
		$end   = DateTime::createFromFormat( '!Y-m-d', $today );
		$start = clone $end;
		$start->modify( '-' . ( $days - 1 ) . ' days' );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT day, total_count, done_count, focus_seconds FROM {$table} WHERE user_id = %d AND day BETWEEN %s AND %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$start->format( 'Y-m-d' ),
				$end->format( 'Y-m-d' )
			),
			ARRAY_A
		);

		$by_day = array();
		foreach ( (array) $rows as $row ) {
			$by_day[ $row['day'] ] = $row;
		}

		$out    = array();
		$cursor = clone $start;
		for ( $i = 0; $i < $days; $i++ ) {
			$key   = $cursor->format( 'Y-m-d' );
			$row   = isset( $by_day[ $key ] ) ? $by_day[ $key ] : null;
			$out[] = array(
				'date'    => $key,
				'total'   => $row ? (int) $row['total_count'] : 0,
				'done'    => $row ? (int) $row['done_count'] : 0,
				'seconds' => $row ? (int) $row['focus_seconds'] : 0,
			);
			$cursor->modify( '+1 day' );
		}
		return $out;
	}
}
