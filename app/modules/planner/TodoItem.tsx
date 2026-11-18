import { __ } from '@wordpress/i18n';
import { sprintf } from '@wordpress/i18n';
import { CheckIcon, PauseIcon, PlayIcon, SparkIcon } from '../../components/Icons';
import { type Item, liveSeconds } from '../../lib/plan';
import { clock, dur } from '../../lib/time';
import { usePlanner } from './store';

/** One row of the day plan: tap = start/pause, round button = done. */
export function TodoItem( { item }: { item: Item } ): JSX.Element {
	const { now, toggle, check, reopen } = usePlanner();
	const seconds = liveSeconds( item, now );
	const running = item.runStart !== null;
	const paused = ! running && ! item.done && seconds > 0;
	const state = item.done ? 'is-done' : running ? 'is-running' : paused ? 'is-paused' : 'is-idle';

	let meta = __( 'Tap to start', 'bw-fokus-log' );
	if ( item.done ) {
		meta = `${ item.start } – ${ item.end }`;
	} else if ( running ) {
		/* translators: %s: start time */
		meta = sprintf( __( 'running · started %s', 'bw-fokus-log' ), item.start );
	} else if ( paused ) {
		meta = __( 'paused · tap to continue', 'bw-fokus-log' );
	}

	const time = running || paused ? clock( seconds ) : item.done ? dur( seconds ) : '';
	const showQuote = ! item.done && item.quote !== '' && ( running || paused );

	return (
		<li className={ `bwfl-item ${ state }` }>
			<div className="bwfl-row">
				<button
					type="button"
					className="bwfl-toggle"
					onClick={ () => toggle( item.id ) }
					disabled={ item.done }
					aria-label={ ( running ? __( 'Pause: ', 'bw-fokus-log' ) : __( 'Start: ', 'bw-fokus-log' ) ) + item.title }
				>
					<span className="bwfl-icon">
						{ item.done ? <CheckIcon /> : running ? <PauseIcon /> : <PlayIcon /> }
					</span>
					<span className="bwfl-text">
						<span className="bwfl-name">{ item.title }</span>
						<span className="bwfl-meta">{ meta }</span>
					</span>
					<span className="bwfl-time">{ time }</span>
				</button>
				{ item.done ? (
					<button
						type="button"
						className="bwfl-check is-on is-done"
						onClick={ () => reopen( item.id ) }
						aria-label={ __( 'Reopen: ', 'bw-fokus-log' ) + item.title }
						title={ __( 'Reopen', 'bw-fokus-log' ) }
					>
						<CheckIcon size={ 20 } />
					</button>
				) : (
					<button
						type="button"
						className={ `bwfl-check${ running || paused ? ' is-on' : '' }` }
						onClick={ () => check( item.id ) }
						aria-label={ __( 'Done: ', 'bw-fokus-log' ) + item.title }
						title={ __( 'Done', 'bw-fokus-log' ) }
					>
						<CheckIcon size={ 20 } />
					</button>
				) }
			</div>
			{ showQuote && (
				<div className="bwfl-quote">
					<SparkIcon />
					<span>
						<span className="bwfl-quote-label">{ __( 'Your coach', 'bw-fokus-log' ) }</span>
						<span className="bwfl-quote-text">{ item.quote }</span>
					</span>
				</div>
			) }
		</li>
	);
}
