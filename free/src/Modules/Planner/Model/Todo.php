<?php
/**
 * One to-do of a day plan.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Modules\Planner\Model;

defined( 'ABSPATH' ) || exit;

/**
 * A line of the plan: `- Title {start|end|duration}`.
 */
final class Todo {

	/**
	 * Creates the value object.
	 *
	 * @param string $title   Text of the to-do.
	 * @param string $start   Start time `hh:mm`, empty when never started.
	 * @param string $end     End time `hh:mm`, empty while not finished.
	 * @param int    $seconds Pure timer time, without breaks.
	 */
	public function __construct(
		public readonly string $title,
		public readonly string $start = '',
		public readonly string $end = '',
		public readonly int $seconds = 0
	) {}

	/**
	 * A to-do is done once it has an end time.
	 */
	public function is_done(): bool {
		return '' !== $this->end;
	}
}
