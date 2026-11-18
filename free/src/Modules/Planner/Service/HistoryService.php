<?php
/**
 * Numbers for the history chart.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Modules\Planner\Service;

use BW\FokusLog\Modules\Planner\Repository\DayRepository;
use BW\FokusLog\Support\Dates;

defined( 'ABSPATH' ) || exit;

/**
 * One entry per day, missing days count as zero.
 */
final class HistoryService {

	/**
	 * Creates the service.
	 *
	 * @param DayRepository $days Storage.
	 */
	public function __construct( private readonly DayRepository $days ) {}

	/**
	 * History ending on `$today`.
	 *
	 * @param int    $user_id Owner.
	 * @param string $today   Last day, `YYYY-MM-DD`.
	 * @param int    $days    Number of days, 1 to 366.
	 * @return array<int, array{date: string, total: int, done: int, seconds: int}>
	 */
	public function last_days( int $user_id, string $today, int $days ): array {
		$keys = Dates::range( $today, max( 1, min( 366, $days ) ) );

		if ( array() === $keys ) {
			return array();
		}

		$stored = $this->days->stats_between( $user_id, $keys[0], $keys[ count( $keys ) - 1 ] );
		$empty  = array(
			'total'   => 0,
			'done'    => 0,
			'seconds' => 0,
		);

		return array_map(
			static fn ( string $key ): array => array( 'date' => $key ) + ( $stored[ $key ] ?? $empty ),
			$keys
		);
	}
}
