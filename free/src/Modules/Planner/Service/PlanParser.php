<?php
/**
 * Turns plan text into to-dos.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Modules\Planner\Service;

use BW\FokusLog\Modules\Planner\Model\Todo;

defined( 'ABSPATH' ) || exit;

/**
 * Parses `- Title` and `- Title {09:05|09:31|00:26}` lines.
 */
final class PlanParser {

	/**
	 * Split the plan into to-dos.
	 *
	 * @param string $text Plan text.
	 * @return Todo[]
	 */
	public function parse( string $text ): array {
		$todos = array();
		$lines = preg_split( '/\r\n|\r|\n/', $text );

		foreach ( false === $lines ? array() : $lines as $line ) {
			if ( 1 !== preg_match( '/^\s*[-•*]\s*(.+?)\s*$/u', $line, $m ) ) {
				continue;
			}

			if ( 1 === preg_match( '/^(.*?)\s*\{([^|}]*)\|([^|}]*)\|([^}]*)\}\s*$/u', $m[1], $b ) ) {
				$todos[] = new Todo( trim( $b[1] ), trim( $b[2] ), trim( $b[3] ), $this->parse_duration( $b[4] ) );
				continue;
			}

			$todos[] = new Todo( $m[1] );
		}

		return $todos;
	}

	/**
	 * Count done to-dos and focus time.
	 *
	 * @param Todo[] $todos To-dos.
	 * @return array{total: int, done: int, seconds: int}
	 */
	public function stats( array $todos ): array {
		$done    = 0;
		$seconds = 0;

		foreach ( $todos as $todo ) {
			$done    += $todo->is_done() ? 1 : 0;
			$seconds += $todo->seconds;
		}

		return array(
			'total'   => count( $todos ),
			'done'    => $done,
			'seconds' => $seconds,
		);
	}

	/**
	 * `hh:mm` or `hh:mm:ss` to seconds.
	 *
	 * @param string $value Duration text.
	 */
	public function parse_duration( string $value ): int {
		$parts = array_map( 'intval', explode( ':', trim( $value ) ) );

		return match ( count( $parts ) ) {
			3       => $parts[0] * 3600 + $parts[1] * 60 + $parts[2],
			2       => $parts[0] * 3600 + $parts[1] * 60,
			default => 0,
		};
	}
}
