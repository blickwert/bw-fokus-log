<?php
/**
 * Tests for the date helpers.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Tests\Unit;

use BW\FokusLog\Support\Dates;

final class DatesTest extends TestCase {

	public function test_validates_real_days_only(): void {
		$this->assertTrue( Dates::is_valid_day( '2026-02-28' ) );
		$this->assertFalse( Dates::is_valid_day( '2026-02-30' ) );
		$this->assertFalse( Dates::is_valid_day( '28.02.2026' ) );
		$this->assertFalse( Dates::is_valid_day( 20260228 ) );
	}

	public function test_range_ends_on_the_given_day_and_crosses_month_ends(): void {
		$this->assertSame( array( '2026-02-27', '2026-02-28', '2026-03-01' ), Dates::range( '2026-03-01', 3 ) );
		$this->assertSame( array(), Dates::range( '2026-03-01', 0 ) );
	}
}
