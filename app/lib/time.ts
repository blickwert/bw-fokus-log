export const pad = ( n: number ): string => String( n ).padStart( 2, '0' );

/** Local `YYYY-MM-DD`. */
export const dateKey = ( d: Date ): string => `${ d.getFullYear() }-${ pad( d.getMonth() + 1 ) }-${ pad( d.getDate() ) }`;

/** Clock time `hh:mm` of a timestamp. */
export const hm = ( ms: number ): string => {
	const d = new Date( ms );
	return `${ pad( d.getHours() ) }:${ pad( d.getMinutes() ) }`;
};

/** Duration for the text format: `hh:mm`. */
export const dur = ( seconds: number ): string => {
	const s = Math.floor( seconds );
	return `${ pad( Math.floor( s / 3600 ) ) }:${ pad( Math.floor( ( s % 3600 ) / 60 ) ) }`;
};

/** Running clock: `m:ss` or `h:mm:ss`. */
export const clock = ( seconds: number ): string => {
	const s = Math.max( 0, Math.floor( seconds ) );
	const h = Math.floor( s / 3600 );
	const m = Math.floor( ( s % 3600 ) / 60 );
	const x = s % 60;
	return h ? `${ h }:${ pad( m ) }:${ pad( x ) }` : `${ m }:${ pad( x ) }`;
};

/** Parse `hh:mm` or `hh:mm:ss` to seconds. */
export const parseDur = ( text: string ): number => {
	const p = text.trim().split( ':' ).map( ( x ) => parseInt( x, 10 ) || 0 );
	if ( p.length === 3 ) {
		return p[ 0 ] * 3600 + p[ 1 ] * 60 + p[ 2 ];
	}
	return p.length === 2 ? p[ 0 ] * 3600 + p[ 1 ] * 60 : 0;
};
