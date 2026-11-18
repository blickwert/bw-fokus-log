import { dur, parseDur } from './time';

/** A to-do as the app works with it. */
export interface Item {
	id: number;
	title: string;
	start: string;
	end: string;
	/** Accumulated timer seconds without the running part. */
	acc: number;
	done: boolean;
	/** Timestamp the timer was last started, null when stopped. */
	runStart: number | null;
	quote: string;
}

let nextId = 1;

/** Plan text to items. Lines: `- Title` or `- Title {start|end|duration}`. */
export function parsePlan( text: string ): Item[] {
	const out: Item[] = [];

	text.split( /\r\n|\r|\n/ ).forEach( ( line ) => {
		const m = line.match( /^\s*[-•*]\s*(.+?)\s*$/ );
		if ( ! m ) {
			return;
		}
		const b = m[ 1 ].match( /^(.*?)\s*\{([^|}]*)\|([^|}]*)\|([^}]*)\}\s*$/ );
		out.push( {
			id: nextId++,
			title: ( b ? b[ 1 ] : m[ 1 ] ).trim(),
			start: b ? b[ 2 ].trim() : '',
			end: b ? b[ 3 ].trim() : '',
			acc: b ? parseDur( b[ 4 ] ) : 0,
			done: b ? b[ 3 ].trim() !== '' : false,
			runStart: null,
			quote: '',
		} );
	} );

	return out;
}

/** Seconds on the clock right now, including a running timer. */
export const liveSeconds = ( item: Item, now: number ): number =>
	item.acc + ( item.runStart ? ( now - item.runStart ) / 1000 : 0 );

/** Items back to the text format. */
export function serializePlan( items: Item[], now: number ): string {
	return items
		.map( ( i ) =>
			i.start ? `- ${ i.title } {${ i.start }|${ i.done ? i.end : '' }|${ dur( liveSeconds( i, now ) ) }}` : `- ${ i.title }`
		)
		.join( '\n' );
}
