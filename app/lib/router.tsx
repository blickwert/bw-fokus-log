import { useEffect, useState } from '@wordpress/element';

/**
 * Tiny router: the current page lives in the `path` query parameter,
 * so PHP and the app build the same addresses (`admin.php?page=…&path=/history`).
 */
const read = (): string => new URLSearchParams( window.location.search ).get( 'path' ) || '/';

export function usePath(): [ string, ( path: string ) => void ] {
	const [ path, setPath ] = useState( read );

	useEffect( () => {
		const onPop = () => setPath( read() );
		window.addEventListener( 'popstate', onPop );
		return () => window.removeEventListener( 'popstate', onPop );
	}, [] );

	const go = ( next: string ) => {
		const url = new URL( window.location.href );
		if ( next === '/' ) {
			url.searchParams.delete( 'path' );
		} else {
			url.searchParams.set( 'path', next );
		}
		window.history.pushState( {}, '', url );
		setPath( next );
	};

	return [ path, go ];
}
