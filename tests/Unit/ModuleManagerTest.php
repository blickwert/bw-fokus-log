<?php
/**
 * Tests for module activation rules.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Tests\Unit;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use BW\FokusLog\Core\AbstractModule;
use BW\FokusLog\Core\ModuleManager;
use BW\FokusLog\Core\Settings;

final class ModuleManagerTest extends TestCase {

	private function module( string $id, array $requires = array() ): AbstractModule {
		return new class( $id, $requires ) extends AbstractModule {
			public bool $registered = false;

			public function __construct( private string $module_id, private array $needs ) {}

			public function id(): string {
				return $this->module_id;
			}

			public function name(): string {
				return $this->module_id;
			}

			public function requires(): array {
				return $this->needs;
			}

			public function register(): void {
				$this->registered = true;
			}
		};
	}

	public function test_module_is_inactive_when_a_required_module_is_switched_off(): void {
		$child = $this->module( 'child', array( 'base' ) );
		$base  = $this->module( 'base' );

		Functions\when( 'get_option' )->justReturn( array( 'base' => false ) );
		Filters\expectApplied( 'bw_fokus_log_register_modules' )->andReturn( array( $base, $child ) );

		$manager = new ModuleManager( new Settings() );
		$manager->boot();

		$this->assertFalse( $base->registered );
		$this->assertFalse( $child->registered );
	}

	public function test_active_modules_register_and_filter_can_veto(): void {
		$one = $this->module( 'one' );
		$two = $this->module( 'two' );

		Functions\when( 'get_option' )->justReturn( array() );
		Filters\expectApplied( 'bw_fokus_log_register_modules' )->andReturn( array( $one, $two ) );
		Filters\expectApplied( 'bw_fokus_log_module_allowed' )->andReturnUsing(
			static fn ( bool $enabled, string $id ): bool => 'two' !== $id && $enabled
		);

		( new ModuleManager( new Settings() ) )->boot();

		$this->assertTrue( $one->registered );
		$this->assertFalse( $two->registered );
	}
}
