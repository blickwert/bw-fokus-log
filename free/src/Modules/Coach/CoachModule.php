<?php
/**
 * Coach module: motivating sayings.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Modules\Coach;

use BW\FokusLog\Core\AbstractModule;
use BW\FokusLog\Modules\Coach\Controller\CoachController;
use BW\FokusLog\Modules\Coach\Service\CoachService;

defined( 'ABSPATH' ) || exit;

/**
 * Supplies the coach texts; works without any other module.
 */
final class CoachModule extends AbstractModule {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'coach';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name(): string {
		return __( 'Coach', 'bw-fokus-log' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description(): string {
		return __( 'Motivating sayings while you work and a party when you tick something off.', 'bw-fokus-log' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		( new CoachController( new CoachService() ) )->register();
	}
}
