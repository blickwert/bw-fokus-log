<?php
/**
 * Defaults shared by all modules.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Base class with sensible defaults, so a module only states what differs.
 */
abstract class AbstractModule implements Module {

	/**
	 * {@inheritDoc}
	 */
	public function description(): string {
		return '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function requires(): array {
		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function enabled_by_default(): bool {
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function activate(): void {}
}
