<?php
/**
 * Tests for saving and loading days.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Tests\Unit;

use Brain\Monkey\Actions;
use BW\FokusLog\Modules\Planner\Model\Day;
use BW\FokusLog\Modules\Planner\Repository\DayRepository;
use BW\FokusLog\Modules\Planner\Service\DayService;
use BW\FokusLog\Modules\Planner\Service\PlanParser;

final class DayServiceTest extends TestCase {

	public function test_save_stores_text_with_derived_numbers_and_fires_hook(): void {
		$repo = $this->createMock( DayRepository::class );
		$repo->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->callback( static fn ( Day $d ): bool => 7 === $d->user_id && '2026-05-04' === $d->date ),
				array( 'total' => 2, 'done' => 1, 'seconds' => 600 )
			);

		Actions\expectDone( 'bw_fokus_log_day_saved' )->once();

		$stats = ( new DayService( $repo, new PlanParser() ) )->save( 7, '2026-05-04', "- A {08:00|08:10|00:10}\n- B" );

		$this->assertSame( 1, $stats['done'] );
	}

	public function test_save_rejects_too_long_text_without_touching_storage(): void {
		$repo = $this->createMock( DayRepository::class );
		$repo->expects( $this->never() )->method( 'save' );

		$this->assertNull( ( new DayService( $repo, new PlanParser() ) )->save( 1, '2026-05-04', str_repeat( 'x', DayService::MAX_LENGTH + 1 ) ) );
	}

	public function test_get_returns_empty_plan_for_unknown_day(): void {
		$repo = $this->createMock( DayRepository::class );
		$repo->method( 'find' )->willReturn( null );

		$this->assertSame(
			array( 'date' => '2026-05-04', 'exists' => false, 'content' => '' ),
			( new DayService( $repo, new PlanParser() ) )->get( 1, '2026-05-04' )
		);
	}
}
