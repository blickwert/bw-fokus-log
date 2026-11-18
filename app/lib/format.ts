import { __, sprintf } from '@wordpress/i18n';

/** Minutes or hours text for a number of seconds: "35 min", "1 h 5 min". */
export function mins( seconds: number ): string {
	const m = Math.round( seconds / 60 );
	if ( m < 60 ) {
		/* translators: %d: minutes */
		return sprintf( __( '%d min', 'bw-fokus-log' ), m );
	}
	/* translators: 1: hours, 2: minutes */
	return sprintf( __( '%1$d h %2$d min', 'bw-fokus-log' ), Math.floor( m / 60 ), m % 60 );
}

/** Number with one decimal, in the user's locale. */
export const decimal = ( n: number, locale: string ): string =>
	new Intl.NumberFormat( locale, { minimumFractionDigits: 1, maximumFractionDigits: 1 } ).format( n );
