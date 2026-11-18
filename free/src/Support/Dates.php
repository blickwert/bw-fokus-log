<?php
/**
 * Small date helpers.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Pure helpers for `YYYY-MM-DD` day keys.
 */
final class Dates {

	/**
	 * Whether the value is a real calendar day like 2026-02-28.
	 *
	 * @param mixed $value Value to check.
	 */
	public static function is_valid_day( mixed $value ): bool {
		if ( ! is_string( $value ) || 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) ) {
			return false;
		}

		return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
	}

	/**
	 * The day keys from `$days - 1` days before `$end` up to `$end`.
	 *
	 * @param string $end  Last day, `YYYY-MM-DD`.
	 * @param int    $days Number of days.
	 * @return string[]
	 */
	public static function range( string $end, int $days ): array {
		$cursor = \DateTimeImmutable::createFromFormat( '!Y-m-d', $end, new \DateTimeZone( 'UTC' ) );

		if ( false === $cursor || $days < 1 ) {
			return array();
		}

		$cursor = $cursor->modify( '-' . ( $days - 1 ) . ' days' );
		$out    = array();

		for ( $i = 0; $i < $days; $i++ ) {
			$out[]  = $cursor->format( 'Y-m-d' );
			$cursor = $cursor->modify( '+1 day' );
		}

		return $out;
	}
}
