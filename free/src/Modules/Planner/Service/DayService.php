<?php
/**
 * Business rules for saving and loading a day.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Modules\Planner\Service;

use BW\FokusLog\Modules\Planner\Model\Day;
use BW\FokusLog\Modules\Planner\Repository\DayRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Stores the plan text and derives the numbers the history needs.
 */
final class DayService {

	public const MAX_LENGTH = 100000;

	/**
	 * Creates the service.
	 *
	 * @param DayRepository $days   Storage.
	 * @param PlanParser    $parser Text parser.
	 */
	public function __construct(
		private readonly DayRepository $days,
		private readonly PlanParser $parser
	) {}

	/**
	 * The plan of one day; empty when none was saved.
	 *
	 * @param int    $user_id Owner.
	 * @param string $date    `YYYY-MM-DD`.
	 * @return array{date: string, exists: bool, content: string}
	 */
	public function get( int $user_id, string $date ): array {
		$day = $this->days->find( $user_id, $date );

		return array(
			'date'    => $date,
			'exists'  => null !== $day,
			'content' => null !== $day ? $day->content : '',
		);
	}

	/**
	 * Save the plan text.
	 *
	 * @param int    $user_id Owner.
	 * @param string $date    `YYYY-MM-DD`.
	 * @param string $content Plan text.
	 * @return array{total: int, done: int, seconds: int}|null Null when the text is too long.
	 */
	public function save( int $user_id, string $date, string $content ): ?array {
		if ( strlen( $content ) > self::MAX_LENGTH ) {
			return null;
		}

		$stats = $this->parser->stats( $this->parser->parse( $content ) );
		$this->days->save( new Day( $user_id, $date, $content ), $stats );

		/**
		 * Fires after a day was saved.
		 *
		 * @param int                                        $user_id Owner.
		 * @param string                                     $date    `YYYY-MM-DD`.
		 * @param array{total: int, done: int, seconds: int} $stats   Numbers derived from the text.
		 */
		do_action( 'bw_fokus_log_day_saved', $user_id, $date, $stats );

		return $stats;
	}
}
