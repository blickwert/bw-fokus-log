<?php
/**
 * Tests for the coach texts.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Tests\Unit;

use Brain\Monkey\Functions;
use BW\FokusLog\Modules\Coach\Service\CoachService;

final class CoachServiceTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'sanitize_text_field' )->returnArg();
	}

	public function test_defaults_are_used_when_nothing_is_saved(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$texts = ( new CoachService() )->get();

		$this->assertNotEmpty( $texts['run'] );
		$this->assertNotEmpty( $texts['allDone'] );
	}

	public function test_saved_lines_replace_defaults_per_group(): void {
		Functions\when( 'get_option' )->justReturn( array( 'run' => array( 'Go {t}!' ), 'titles' => array(), 'allDone' => '' ) );

		$texts = ( new CoachService() )->get();

		$this->assertSame( array( 'Go {t}!' ), $texts['run'] );
		$this->assertNotEmpty( $texts['titles'], 'Empty group falls back to defaults.' );
	}

	public function test_save_trims_and_drops_empty_lines(): void {
		$stored = null;
		Functions\when( 'update_option' )->alias(
			static function ( string $name, $value ) use ( &$stored ): bool {
				$stored = $value;
				return true;
			}
		);
		Functions\when( 'get_option' )->alias( static fn () => $stored );

		( new CoachService() )->save( array( 'run' => "  A \n\n B", 'done' => array( '', 'C' ), 'allDone' => 'Done' ) );

		$this->assertSame( array( 'A', 'B' ), $stored['run'] );
		$this->assertSame( array( 'C' ), $stored['done'] );
		$this->assertSame( 'Done', $stored['allDone'] );
	}
}
