<?php
/**
 * Speicherung: eine Zeile pro Benutzer und Tag.
 * Der Text im Format „- To-do {start|ende|dauer}“ ist die Quelle der Wahrheit,
 * die Zahlen (erledigt, Fokuszeit) werden beim Speichern daraus berechnet –
 * damit der Verlauf schnell geladen werden kann.
 *
 * Jede Speicherung zählt die Revision (rev) hoch. Ein Gerät schickt die Revision mit,
 * auf der seine Änderung beruht; hat inzwischen ein anderes Gerät gespeichert, wird
 * nichts überschrieben (Konflikt), und das Gerät lädt den neuen Stand.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Fokus_Log_Store {

	const DB_VERSION = '2';
	const MAX_LENGTH = 100000;

	/** Höchste Dauer eines To-dos (24 Stunden), gegen Tippfehler und Überlauf. */
	const MAX_SECONDS = 86400;

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
			rev int(10) unsigned NOT NULL DEFAULT 0,
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

	/**
	 * „hh:mm:ss“ oder „hh:mm“ in Sekunden, begrenzt auf 0 bis 24 Stunden.
	 */
	public static function parse_duration( $value ) {
		$parts = array_map( 'intval', explode( ':', trim( (string) $value ) ) );
		$secs  = 0;
		if ( 3 === count( $parts ) ) {
			$secs = $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
		} elseif ( 2 === count( $parts ) ) {
			$secs = $parts[0] * 3600 + $parts[1] * 60;
		}
		return max( 0, min( self::MAX_SECONDS, $secs ) );
	}

	/**
	 * Text so speichern, wie er geschrieben wurde: nur ungültige Zeichen und Steuerzeichen
	 * entfernen, Zeilenenden vereinheitlichen. Kein HTML-Filter – die App zeigt den Text
	 * immer maskiert an, „<“, „&“ und Anführungszeichen bleiben so erhalten.
	 */
	public static function clean( $text ) {
		$text = wp_check_invalid_utf8( (string) $text, true );
		$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
		return (string) preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text );
	}

	public static function get_day( $user_id, $day ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT content, rev, updated_at FROM {$table} WHERE user_id = %d AND day = %s", $user_id, $day ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
	}

	/**
	 * Speichert den Text eines Tages.
	 *
	 * $base = Revision, auf der die Änderung beruht (null = ohne Prüfung). Liefert die Zahlen
	 * mit der neuen Revision, 'conflict', wenn ein anderes Gerät inzwischen gespeichert hat,
	 * oder 'db_error'.
	 */
	public static function save_day( $user_id, $day, $content, $base = null ) {
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
		$total = min( 65535, count( $items ) );
		$done  = min( 65535, $done );
		$secs  = min( 4294967295, $secs );
		$now   = current_time( 'mysql' );

		$update = static function ( $check ) use ( $wpdb, $table, $content, $total, $done, $secs, $now, $user_id, $day, $base ) {
			$sql = "UPDATE {$table} SET content = %s, total_count = %d, done_count = %d, focus_seconds = %d, updated_at = %s, rev = rev + 1 WHERE user_id = %d AND day = %s";
			$arg = array( $content, $total, $done, $secs, $now, (int) $user_id, $day );
			if ( $check ) {
				$sql  .= ' AND rev = %d';
				$arg[] = (int) $base;
			}
			return $wpdb->query( $wpdb->prepare( $sql, $arg ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		};

		// Vorhandenen Tag ändern – mit Revisionsprüfung, wenn eine Revision mitkommt. Tage aus
		// Version 1.0.0 haben die Revision 0 und werden so ebenfalls gefunden.
		$rows = $update( null !== $base );
		if ( false === $rows ) {
			return 'db_error';
		}
		$saved = $rows > 0;
		// Neuen Tag anlegen. Legt ein anderes Gerät ihn im selben Moment an, schlägt das
		// Einfügen am eindeutigen Schlüssel fehl, und es gibt keinen stillen Verlust.
		if ( ! $saved && ( null === $base || 0 === $base ) ) {
			$suppress = $wpdb->suppress_errors( true );
			$saved    = (bool) $wpdb->insert(
				$table,
				array(
					'user_id'       => (int) $user_id,
					'day'           => $day,
					'content'       => $content,
					'total_count'   => $total,
					'done_count'    => $done,
					'focus_seconds' => $secs,
					'rev'           => 1,
					'updated_at'    => $now,
				),
				array( '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%s' )
			);
			$wpdb->suppress_errors( $suppress );
			if ( ! $saved && null === $base ) {
				// Ohne Prüfung: der Tag ist inzwischen da, also ändern.
				$saved = (int) $update( false ) > 0;
				if ( ! $saved ) {
					return 'db_error';
				}
			}
		}
		if ( ! $saved ) {
			return 'conflict';
		}
		$row = self::get_day( $user_id, $day );

		return array(
			'total'   => $total,
			'done'    => $done,
			'seconds' => $secs,
			'rev'     => $row ? (int) $row['rev'] : 0,
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
