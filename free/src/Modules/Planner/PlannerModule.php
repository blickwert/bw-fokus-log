<?php
/**
 * Planner module: day plan, timer data, history.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Modules\Planner;

use BW\FokusLog\Core\AbstractModule;
use BW\FokusLog\Modules\Planner\Controller\AppController;
use BW\FokusLog\Modules\Planner\Controller\RestController;
use BW\FokusLog\Modules\Planner\Repository\DayRepository;
use BW\FokusLog\Modules\Planner\Repository\PreferencesRepository;
use BW\FokusLog\Modules\Planner\Service\DayService;
use BW\FokusLog\Modules\Planner\Service\HistoryService;
use BW\FokusLog\Modules\Planner\Service\PlanParser;
use BW\FokusLog\Modules\Planner\Service\PreferencesService;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the planner classes together; holds no logic itself.
 */
final class PlannerModule extends AbstractModule {

	/**
	 * {@inheritDoc}
	 */
	public function id(): string {
		return 'planner';
	}

	/**
	 * {@inheritDoc}
	 */
	public function name(): string {
		return __( 'Planner', 'bw-fokus-log' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description(): string {
		return __( 'Day plan as text, timer per to-do, breaks and history.', 'bw-fokus-log' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		$days        = new DayRepository();
		$preferences = new PreferencesService( new PreferencesRepository() );

		$days->maybe_upgrade();

		( new RestController( new DayService( $days, new PlanParser() ), new HistoryService( $days ), $preferences ) )->register();
		( new AppController( $preferences ) )->register();
	}

	/**
	 * {@inheritDoc}
	 */
	public function activate(): void {
		( new DayRepository() )->install();
	}
}
