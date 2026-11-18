<?php
/**
 * Finds modules and registers the active ones.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Core;

use BW\FokusLog\Modules\Coach\CoachModule;
use BW\FokusLog\Modules\Planner\PlannerModule;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps the list of modules and decides which of them run.
 */
final class ModuleManager {

	/**
	 * Known modules by id.
	 *
	 * @var array<string, Module>
	 */
	private array $modules = array();

	/**
	 * Creates the manager.
	 *
	 * @param Settings $settings Plugin settings.
	 */
	public function __construct( private readonly Settings $settings ) {}

	/**
	 * Collect all modules, including those added by other plugins.
	 *
	 * @return array<string, Module>
	 */
	public function discover(): array {
		if ( array() === $this->modules ) {
			$found = array(
				new PlannerModule(),
				new CoachModule(),
			);

			/**
			 * Lets add-ons (for example a Pro plugin) add modules.
			 *
			 * @param Module[] $found Modules known so far.
			 */
			$found = apply_filters( 'bw_fokus_log_register_modules', $found );

			foreach ( $found as $module ) {
				if ( $module instanceof Module ) {
					$this->modules[ $module->id() ] = $module;
				}
			}
		}

		return $this->modules;
	}

	/**
	 * Whether a module is switched on, including its requirements.
	 *
	 * @param string $id Module id.
	 */
	public function is_active( string $id ): bool {
		$modules = $this->discover();

		if ( ! isset( $modules[ $id ] ) ) {
			return false;
		}

		$states  = $this->settings->module_states();
		$enabled = $states[ $id ] ?? $modules[ $id ]->enabled_by_default();

		/**
		 * Lets add-ons veto a module, for example because of a missing licence.
		 *
		 * @param bool   $enabled Whether the module is allowed to run.
		 * @param string $id      Module id.
		 */
		$enabled = (bool) apply_filters( 'bw_fokus_log_module_allowed', $enabled, $id );

		if ( ! $enabled ) {
			return false;
		}

		foreach ( $modules[ $id ]->requires() as $required ) {
			if ( ! $this->is_active( $required ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Register every active module.
	 */
	public function boot(): void {
		foreach ( $this->discover() as $id => $module ) {
			if ( $this->is_active( $id ) ) {
				$module->register();
			}
		}
	}

	/**
	 * Let every module prepare its storage.
	 */
	public function activate(): void {
		foreach ( $this->discover() as $module ) {
			$module->activate();
		}
	}
}
