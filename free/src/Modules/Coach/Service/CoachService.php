<?php
/**
 * Coach sayings.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Modules\Coach\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the motivating texts and lets an administrator replace them.
 */
final class CoachService {

	public const OPTION = 'bw_fokus_log_coach';

	/**
	 * Keys that hold a list of lines.
	 *
	 * @var string[]
	 */
	private const LIST_KEYS = array( 'run', 'titles', 'done' );

	/**
	 * Built-in texts in the current language.
	 * Placeholders: {t} to-do, {d} duration, {n} done, {total} all.
	 *
	 * @return array{run: string[], titles: string[], done: string[], allDone: string}
	 */
	public function defaults(): array {
		return array(
			'run'     => array(
				__( 'You started – that was the hardest part. The rest is routine.', 'bw-fokus-log' ),
				__( 'Phone face down, focus on. I will watch the clock.', 'bw-fokus-log' ),
				__( 'Small step, big effect. Keep going!', 'bw-fokus-log' ),
				__( 'The social media world can do without you for a few minutes. Promise.', 'bw-fokus-log' ),
				__( 'Sudden idea? Note it quickly, celebrate later. Only this matters now.', 'bw-fokus-log' ),
				__( 'You are the opposite of "I will do it in a bit" right now: you are doing it.', 'bw-fokus-log' ),
				__( 'Not perfect – just done. That is plenty.', 'bw-fokus-log' ),
				__( '"{t}" is already trembling. It knows you are coming.', 'bw-fokus-log' ),
				__( 'Every minute here is one minute less weight on your mind.', 'bw-fokus-log' ),
				__( 'Focus mode: on. Excuse mode: offline.', 'bw-fokus-log' ),
				__( 'Like cycling: pedal steadily, do not sprint.', 'bw-fokus-log' ),
				__( 'The timer runs – and so do you. Strong!', 'bw-fokus-log' ),
				__( 'Act first, think later. The other way round, for once.', 'bw-fokus-log' ),
				__( 'You are collecting proof that you can do it.', 'bw-fokus-log' ),
				__( 'Just keep going. I will tell you when it is party time.', 'bw-fokus-log' ),
				__( 'You are building momentum right now. Do not brake!', 'bw-fokus-log' ),
				__( 'Whoever starts "{t}" has already half won.', 'bw-fokus-log' ),
				__( 'Breathe, relax your shoulders – you have this under control.', 'bw-fokus-log' ),
			),
			'titles'  => array(
				__( 'Done!', 'bw-fokus-log' ),
				__( 'Zap, finished!', 'bw-fokus-log' ),
				__( 'Boom!', 'bw-fokus-log' ),
				__( 'You are on a roll!', 'bw-fokus-log' ),
				__( 'Ticked off!', 'bw-fokus-log' ),
				__( 'Yes!', 'bw-fokus-log' ),
				__( 'Strong!', 'bw-fokus-log' ),
			),
			'done'    => array(
				__( '"{t}" is history. Off to the success list!', 'bw-fokus-log' ),
				__( 'One less on your mind. Feels good, right?', 'bw-fokus-log' ),
				__( 'That was not a to-do, that was a statement.', 'bw-fokus-log' ),
				__( 'Finished in {d}. Clean work!', 'bw-fokus-log' ),
				__( 'You kept your word – to yourself. That counts most.', 'bw-fokus-log' ),
				__( 'High five! Enjoy the break, you earned it.', 'bw-fokus-log' ),
				__( 'Take the momentum with you – the next one gets easier.', 'bw-fokus-log' ),
				__( 'One more tick. Your future self says thanks.', 'bw-fokus-log' ),
				__( 'See? Not as bad as it felt.', 'bw-fokus-log' ),
				__( 'The to-do list just gained respect for you.', 'bw-fokus-log' ),
				__( '{n} of {total} done. Going well!', 'bw-fokus-log' ),
				__( 'This is how it works: just do it.', 'bw-fokus-log' ),
			),
			'allDone' => __( 'The whole plan is ticked off. Today you are the boss – celebrate!', 'bw-fokus-log' ),
		);
	}

	/**
	 * Texts to use: the saved ones where present, otherwise the defaults.
	 *
	 * @return array{run: string[], titles: string[], done: string[], allDone: string}
	 */
	public function get(): array {
		$defaults = $this->defaults();
		$saved    = get_option( self::OPTION, array() );
		$saved    = is_array( $saved ) ? $saved : array();

		foreach ( self::LIST_KEYS as $key ) {
			if ( isset( $saved[ $key ] ) && is_array( $saved[ $key ] ) && array() !== $saved[ $key ] ) {
				$defaults[ $key ] = array_values( array_map( 'strval', $saved[ $key ] ) );
			}
		}

		if ( isset( $saved['allDone'] ) && '' !== $saved['allDone'] ) {
			$defaults['allDone'] = (string) $saved['allDone'];
		}

		return $defaults;
	}

	/**
	 * Save new texts. Empty parts fall back to the defaults again.
	 *
	 * @param array<string, mixed> $input Raw input with `run`, `titles`, `done`, `allDone`.
	 * @return array{run: string[], titles: string[], done: string[], allDone: string} The texts now in use.
	 */
	public function save( array $input ): array {
		$clean = array();

		foreach ( self::LIST_KEYS as $key ) {
			$clean[ $key ] = $this->clean_lines( $input[ $key ] ?? array() );
		}

		$clean['allDone'] = sanitize_text_field( (string) ( $input['allDone'] ?? '' ) );

		update_option( self::OPTION, $clean, false );

		return $this->get();
	}

	/**
	 * Trim, sanitise and drop empty lines.
	 *
	 * @param mixed $lines List of strings or a multi-line string.
	 * @return string[]
	 */
	private function clean_lines( mixed $lines ): array {
		if ( is_string( $lines ) ) {
			$lines = preg_split( '/\r\n|\r|\n/', $lines );
		}

		if ( ! is_array( $lines ) ) {
			return array();
		}

		$out = array();
		foreach ( $lines as $line ) {
			$line = trim( sanitize_text_field( (string) $line ) );
			if ( '' !== $line ) {
				$out[] = $line;
			}
		}

		return $out;
	}
}
