<?php
/**
 * Plugin-wide settings (which modules are switched on).
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the module switches from the options table.
 */
final class Settings {

	public const OPTION_MODULES = 'bw_fokus_log_modules';

	/**
	 * Stored on/off state per module id, empty when never changed.
	 *
	 * @return array<string, bool>
	 */
	public function module_states(): array {
		$stored = get_option( self::OPTION_MODULES, array() );

		return is_array( $stored ) ? array_map( 'boolval', $stored ) : array();
	}
}
