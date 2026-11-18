<?php
/**
 * Per-user preferences.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Modules\Planner\Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Stores the break length in user meta.
 */
class PreferencesRepository {

	public const META_PAUSE = 'bw_fokus_log_pause';

	/**
	 * Break length in minutes, or null when the user never set one.
	 *
	 * @param int $user_id User.
	 */
	public function pause( int $user_id ): ?int {
		$value = get_user_meta( $user_id, self::META_PAUSE, true );

		return '' === $value ? null : (int) $value;
	}

	/**
	 * Save the break length.
	 *
	 * @param int $user_id User.
	 * @param int $minutes Minutes.
	 */
	public function set_pause( int $user_id, int $minutes ): void {
		update_user_meta( $user_id, self::META_PAUSE, $minutes );
	}
}
