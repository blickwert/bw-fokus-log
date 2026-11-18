import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { config } from '../../lib/config';
import { extendList } from '../../lib/extend';
import { mins } from '../../lib/format';
import { CoachSettings } from './CoachSettings';

/**
 * Coach module: hooks into the planner through filters only,
 * so either module works without the other.
 */
const lastPick: Record< string, number > = {};

function pick( list: string[], key: string ): string {
	if ( ! list.length ) {
		return '';
	}
	let k: number;
	do {
		k = Math.floor( Math.random() * list.length );
	} while ( list.length > 1 && k === lastPick[ key ] );
	lastPick[ key ] = k;
	return list[ k ];
}

if ( config.coach ) {
	addFilter( 'bw-fokus-log.run-quote', 'bw-fokus-log/coach', ( _value: unknown, title: unknown ) =>
		pick( config.coach?.run ?? [], 'run' ).split( '{t}' ).join( String( title ) )
	);

	addFilter( 'bw-fokus-log.celebration', 'bw-fokus-log/coach', ( _value: unknown, data: unknown ) => {
		const { title, seconds, done, total } = data as { title: string; seconds: number; done: number; total: number };
		const texts = config.coach;
		if ( ! texts ) {
			return null;
		}
		if ( done === total ) {
			return { title: __( 'All done!', 'bw-fokus-log' ), text: texts.allDone };
		}
		return {
			title: pick( texts.titles, 'titles' ) || __( 'Done!', 'bw-fokus-log' ),
			text: pick( texts.done, 'done' )
				.split( '{t}' ).join( title )
				.split( '{d}' ).join( mins( seconds ) )
				.split( '{n}' ).join( String( done ) )
				.split( '{total}' ).join( String( total ) ),
		};
	} );

	if ( config.canEdit ) {
		extendList( 'bw-fokus-log.settings-panels', 'bw-fokus-log/coach', { id: 'coach', component: CoachSettings } );
	}
}
