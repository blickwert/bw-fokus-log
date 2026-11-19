<?php
/**
 * REST-Schnittstelle: /wp-json/fokus-log/v1/…
 *   GET  day?date=YYYY-MM-DD          → { date, content, exists, rev }
 *   POST day  { date, content, base } → { saved, total, done, seconds, rev }
 *        base = Revision, auf der die Änderung beruht; hat ein anderes Gerät inzwischen
 *        gespeichert: 409 fokus_log_conflict mit dem aktuellen Stand in data.current
 *   GET  history?today=…&days=14      → [ { date, total, done, seconds } ]
 *   POST settings { pause }           → { pause }
 * Jeder angemeldete Benutzer sieht nur seine eigenen Daten.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Fokus_Log_Rest {

	const NS = 'fokus-log/v1';

	public static function register() {
		$date_arg = array(
			'required'          => true,
			'type'              => 'string',
			'validate_callback' => array( __CLASS__, 'valid_date' ),
		);

		register_rest_route(
			self::NS,
			'/day',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_day' ),
					'permission_callback' => array( __CLASS__, 'can_use' ),
					'args'                => array( 'date' => $date_arg ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'save_day' ),
					'permission_callback' => array( __CLASS__, 'can_use' ),
					'args'                => array(
						'date'    => $date_arg,
						'content' => array(
							'required' => true,
							'type'     => 'string',
						),
						'base'    => array(
							'type'    => 'integer',
							'minimum' => 0,
						),
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/history',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'history' ),
				'permission_callback' => array( __CLASS__, 'can_use' ),
				'args'                => array(
					'today' => $date_arg,
					'days'  => array(
						'type'    => 'integer',
						'default' => 14,
						'minimum' => 1,
						'maximum' => 366,
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/settings',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'save_settings' ),
				'permission_callback' => array( __CLASS__, 'can_use' ),
				'args'                => array(
					'pause' => array(
						'required' => true,
						'type'     => 'integer',
						'minimum'  => 0,
						'maximum'  => 120,
					),
				),
			)
		);
	}

	public static function can_use() {
		return is_user_logged_in();
	}

	public static function valid_date( $value ) {
		if ( ! is_string( $value ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) ) {
			return false;
		}
		return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
	}

	public static function get_day( WP_REST_Request $request ) {
		return rest_ensure_response( self::day( $request['date'] ) );
	}

	/** Der gespeicherte Stand eines Tages des aktuellen Benutzers. */
	private static function day( $date ) {
		$row = Fokus_Log_Store::get_day( get_current_user_id(), $date );
		return array(
			'date'    => $date,
			'exists'  => (bool) $row,
			'content' => $row ? $row['content'] : '',
			'rev'     => $row ? (int) $row['rev'] : 0,
		);
	}

	public static function save_day( WP_REST_Request $request ) {
		$content = Fokus_Log_Store::clean( $request['content'] );
		if ( strlen( $content ) > Fokus_Log_Store::MAX_LENGTH ) {
			return new WP_Error( 'fokus_log_too_long', 'Der Tagesplan ist zu lang.', array( 'status' => 400 ) );
		}
		$base  = null === $request['base'] ? null : (int) $request['base'];
		$stats = Fokus_Log_Store::save_day( get_current_user_id(), $request['date'], $content, $base );
		if ( 'conflict' === $stats ) {
			return new WP_Error(
				'fokus_log_conflict',
				'Der Tagesplan wurde inzwischen auf einem anderen Gerät geändert.',
				array(
					'status'  => 409,
					'current' => self::day( $request['date'] ),
				)
			);
		}
		if ( ! is_array( $stats ) ) {
			return new WP_Error( 'fokus_log_db', 'Der Tagesplan konnte nicht gespeichert werden.', array( 'status' => 500 ) );
		}
		return rest_ensure_response( array_merge( array( 'saved' => true ), $stats ) );
	}

	public static function history( WP_REST_Request $request ) {
		$days = max( 1, min( 366, (int) $request['days'] ) );
		return rest_ensure_response( Fokus_Log_Store::history( get_current_user_id(), $request['today'], $days ) );
	}

	public static function save_settings( WP_REST_Request $request ) {
		$pause = max( 0, min( 120, (int) $request['pause'] ) );
		update_user_meta( get_current_user_id(), 'fokus_log_pause', $pause );
		return rest_ensure_response( array( 'pause' => $pause ) );
	}
}
