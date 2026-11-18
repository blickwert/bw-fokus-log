<?php
/**
 * Tests for the plan parser.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Tests\Unit;

use BW\FokusLog\Modules\Planner\Service\PlanParser;
use PHPUnit\Framework\Attributes\DataProvider;

final class PlanParserTest extends TestCase {

	public function test_parses_plain_and_timed_lines(): void {
		$todos = ( new PlanParser() )->parse( "- Walk {08:30|09:05|00:35}\n- Make bed {09:10||00:06}\nignored\n* Vacuum" );

		$this->assertCount( 3, $todos );
		$this->assertSame( 'Walk', $todos[0]->title );
		$this->assertTrue( $todos[0]->is_done() );
		$this->assertSame( 2100, $todos[0]->seconds );
		$this->assertFalse( $todos[1]->is_done(), 'Empty end time means started but not finished.' );
		$this->assertSame( 'Vacuum', $todos[2]->title );
		$this->assertSame( '', $todos[2]->start );
	}

	public function test_stats_count_done_and_focus_time(): void {
		$parser = new PlanParser();
		$stats  = $parser->stats( $parser->parse( "- A {08:00|08:10|00:10}\n- B {09:00||00:05}\n- C" ) );

		$this->assertSame( array( 'total' => 3, 'done' => 1, 'seconds' => 900 ), $stats );
	}

	#[DataProvider( 'durations' )]
	public function test_parse_duration( string $input, int $expected ): void {
		$this->assertSame( $expected, ( new PlanParser() )->parse_duration( $input ) );
	}

	/**
	 * @return array<string, array{string, int}>
	 */
	public static function durations(): array {
		return array(
			'hours and minutes' => array( '01:30', 5400 ),
			'with seconds'      => array( '00:01:05', 65 ),
			'garbage'           => array( 'abc', 0 ),
			'empty'             => array( '', 0 ),
		);
	}
}
