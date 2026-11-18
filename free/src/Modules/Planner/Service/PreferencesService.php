<?php
/**
 * User preferences.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Modules\Planner\Service;

use BW\FokusLog\Modules\Planner\Repository\PreferencesRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Break length between two to-dos.
 */
final class PreferencesService {

	public const DEFAULT_PAUSE = 5;
	public const MAX_PAUSE     = 120;

	/**
	 * Creates the service.
	 *
	 * @param PreferencesRepository $preferences Storage.
	 */
	public function __construct( private readonly PreferencesRepository $preferences ) {}

	/**
	 * Break length in minutes for a user.
	 *
	 * @param int $user_id User.
	 */
	public function pause( int $user_id ): int {
		$saved = $this->preferences->pause( $user_id );

		/**
		 * Default break length in minutes for users who never chose one.
		 *
		 * @param int $minutes Minutes.
		 */
		return $saved ?? max( 0, (int) apply_filters( 'bw_fokus_log_default_pause', self::DEFAULT_PAUSE ) );
	}

	/**
	 * Save the break length, clamped to a sensible range.
	 *
	 * @param int $user_id User.
	 * @param int $minutes Minutes.
	 * @return int The stored value.
	 */
	public function set_pause( int $user_id, int $minutes ): int {
		$minutes = max( 0, min( self::MAX_PAUSE, $minutes ) );
		$this->preferences->set_pause( $user_id, $minutes );

		return $minutes;
	}
}
