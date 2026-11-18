<?php
/**
 * Tests for the history numbers.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Tests\Unit;

use BW\FokusLog\Modules\Planner\Repository\DayRepository;
use BW\FokusLog\Modules\Planner\Service\HistoryService;

final class HistoryServiceTest extends TestCase {

	public function test_missing_days_count_as_zero(): void {
		$repo = $this->createMock( DayRepository::class );
		$repo->expects( $this->once() )
			->method( 'stats_between' )
			->with( 3, '2026-05-02', '2026-05-04' )
			->willReturn( array( '2026-05-03' => array( 'total' => 4, 'done' => 3, 'seconds' => 1800 ) ) );

		$rows = ( new HistoryService( $repo ) )->last_days( 3, '2026-05-04', 3 );

		$this->assertSame( array( '2026-05-02', '2026-05-03', '2026-05-04' ), array_column( $rows, 'date' ) );
		$this->assertSame( array( 0, 3, 0 ), array_column( $rows, 'done' ) );
		$this->assertSame( 1800, $rows[1]['seconds'] );
	}
}
