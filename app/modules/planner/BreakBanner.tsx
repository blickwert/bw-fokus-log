import { __, sprintf } from '@wordpress/i18n';
import { clock } from '../../lib/time';
import { usePlanner } from './store';

/** Countdown between two to-dos with a fixed end. */
export function BreakBanner(): JSX.Element | null {
	const { brk, items, now, startNext } = usePlanner();
	const next = brk ? items.find( ( i ) => i.id === brk.next ) : undefined;

	if ( ! brk || ! next ) {
		return null;
	}

	const left = ( brk.until - now ) / 1000;

	return (
		<div className="bwfl-break" role="status">
			<div>
				<div className="bwfl-break-title">{ left > 0 ? __( 'Break – well done.', 'bw-fokus-log' ) : __( 'Break over – let’s go.', 'bw-fokus-log' ) }</div>
				{ /* translators: %s: title of the next to-do */ }
				<div className="bwfl-break-next">{ sprintf( __( 'Next: %s', 'bw-fokus-log' ), next.title ) }</div>
			</div>
			<div className="bwfl-break-right">
				<span className="bwfl-break-time">{ clock( left ) }</span>
				<button type="button" className="bwfl-btn bwfl-btn-warm" onClick={ startNext }>
					{ __( 'Start next', 'bw-fokus-log' ) }
				</button>
			</div>
		</div>
	);
}
