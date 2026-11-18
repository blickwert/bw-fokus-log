<?php
/**
 * A stored day.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Modules\Planner\Model;

defined( 'ABSPATH' ) || exit;

/**
 * The plan text of one user on one day.
 */
final class Day {

	/**
	 * Creates the value object.
	 *
	 * @param int    $user_id    Owner.
	 * @param string $date       `YYYY-MM-DD`.
	 * @param string $content    Plan text.
	 * @param string $updated_at MySQL datetime of the last save.
	 */
	public function __construct(
		public readonly int $user_id,
		public readonly string $date,
		public readonly string $content,
		public readonly string $updated_at = ''
	) {}
}
