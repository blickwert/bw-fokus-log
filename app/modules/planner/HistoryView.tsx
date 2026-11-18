import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { api } from '../../lib/api';
import { config } from '../../lib/config';
import { decimal, mins } from '../../lib/format';
import { usePlanner } from './store';

interface Row {
	date: string;
	total: number;
	done: number;
	seconds: number;
}

const sum = ( rows: Row[], key: 'done' | 'seconds' ): number => rows.reduce( ( a, r ) => a + r[ key ], 0 );

/** "History": last 14 days compared with the week before. */
export function HistoryView(): JSX.Element {
	const planner = usePlanner();
	const [ rows, setRows ] = useState< Row[] | null >( null );

	useEffect( () => {
		api< Row[] >( '/history', { params: { today: planner.date, days: 14 } } )
			.then( setRows )
			.catch( () => setRows( [] ) );
	}, [ planner.date ] );

	if ( rows === null ) {
		return <p className="bwfl-loading">{ __( 'Loading history …', 'bw-fokus-log' ) }</p>;
	}
	if ( rows.length === 0 ) {
		return <p className="bwfl-error">{ __( 'The history could not be loaded.', 'bw-fokus-log' ) }</p>;
	}

	// Today comes live from the running plan, not from the last save.
	const data = rows.map( ( r ) => ( r.date === planner.date ? { ...r, done: planner.doneCount, seconds: planner.focusSeconds } : r ) );
	const today = data[ data.length - 1 ];
	const current = data.slice( -7 );
	const previous = data.slice( -14, -7 );
	const avgDone = sum( current, 'done' ) / current.length;
	const prevDone = previous.length ? sum( previous, 'done' ) / previous.length : 0;
	const diff = avgDone - prevDone;
	const max = Math.max( 12, ...data.map( ( r ) => r.done ) );
	const weekday = new Intl.DateTimeFormat( config.locale, { weekday: 'short' } );

	return (
		<div className="bwfl-stack">
			<div className="bwfl-stats">
				<div className="bwfl-card bwfl-stat">
					<span>{ __( 'Today', 'bw-fokus-log' ) }</span>
					{ /* translators: %d: number of to-dos */ }
					<strong>{ sprintf( __( '%d to-dos', 'bw-fokus-log' ), today.done ) }</strong>
					{ /* translators: %s: duration */ }
					<span>{ sprintf( __( '%s focus', 'bw-fokus-log' ), mins( today.seconds ) ) }</span>
				</div>
				<div className="bwfl-card bwfl-stat">
					<span>{ __( 'Avg. to-dos per day, last 7 days', 'bw-fokus-log' ) }</span>
					<strong>{ decimal( avgDone, config.locale ) }</strong>
					<span className="bwfl-accent">
						{ /* translators: %s: signed difference */ }
						{ sprintf( __( '%s compared to last week', 'bw-fokus-log' ), ( diff >= 0 ? '+' : '' ) + decimal( diff, config.locale ) ) }
					</span>
				</div>
				<div className="bwfl-card bwfl-stat">
					<span>{ __( 'Avg. focus time per day, last 7 days', 'bw-fokus-log' ) }</span>
					<strong>{ mins( sum( current, 'seconds' ) / current.length ) }</strong>
					{ /* translators: %s: duration */ }
					<span>{ sprintf( __( 'Last week: %s', 'bw-fokus-log' ), previous.length ? mins( sum( previous, 'seconds' ) / previous.length ) : '–' ) }</span>
				</div>
			</div>

			<section className="bwfl-card" aria-labelledby="bwfl-chart-h">
				<div className="bwfl-card-head">
					<h2 id="bwfl-chart-h">{ __( 'Done to-dos per day · 14 days', 'bw-fokus-log' ) }</h2>
					<span className="bwfl-note">{ __( 'Today is live', 'bw-fokus-log' ) }</span>
				</div>
				<div className="bwfl-chart-wrap">
					<div className="bwfl-chart" role="img" aria-label={ __( 'Bar chart of the done to-dos of the last 14 days', 'bw-fokus-log' ) }>
						{ data.map( ( r ) => {
							const d = new Date( r.date + 'T12:00:00' );
							const isToday = r.date === planner.date;
							return (
								<div key={ r.date } className={ `bwfl-col${ isToday ? ' is-today' : '' }` }>
									<span className="bwfl-col-val">{ r.done }</span>
									<span className="bwfl-col-track">
										<span className="bwfl-col-bar" style={ { height: Math.max( 4, Math.round( ( r.done / max ) * 200 ) ) } } />
									</span>
									<span className="bwfl-col-day">{ isToday ? __( 'Today', 'bw-fokus-log' ) : weekday.format( d ) }</span>
									<span className="bwfl-col-date">{ d.getDate() }.</span>
									<span className="bwfl-col-min">{ mins( r.seconds ) }</span>
								</div>
							);
						} ) }
					</div>
				</div>
				<p className="bwfl-hint">{ __( 'The history simply reads the saved day plans – empty days are breaks, not setbacks.', 'bw-fokus-log' ) }</p>
			</section>
		</div>
	);
}
