<?php
/**
 * Contract every feature module implements.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Core;

defined( 'ABSPATH' ) || exit;

/**
 * A self-contained feature that can be switched on and off.
 */
interface Module {

	/**
	 * Stable identifier, stored in the settings. Never rename after a release.
	 */
	public function id(): string;

	/**
	 * Human readable name.
	 */
	public function name(): string;

	/**
	 * One-sentence description.
	 */
	public function description(): string;

	/**
	 * IDs of modules that must be active for this one to work.
	 *
	 * @return string[]
	 */
	public function requires(): array;

	/**
	 * Whether the module is active until the site owner changes it.
	 */
	public function enabled_by_default(): bool;

	/**
	 * Hook the module into WordPress. Called only for active modules.
	 */
	public function register(): void;

	/**
	 * Create database tables etc. Called on plugin activation for every module.
	 */
	public function activate(): void;
}
