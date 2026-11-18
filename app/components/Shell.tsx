import { __ } from '@wordpress/i18n';
import { config } from '../lib/config';
import { providers, routes } from '../lib/extend';
import { usePath } from '../lib/router';

/** Page frame: heading, tabs and the route the address asks for. */
export function Shell(): JSX.Element {
	const [ path, go ] = usePath();
	const list = routes();
	const current = list.find( ( r ) => r.path === path ) ?? list[ 0 ];
	const date = new Intl.DateTimeFormat( config.locale, { weekday: 'long', day: 'numeric', month: 'long' } ).format( new Date() );

	if ( ! current ) {
		return <p className="bwfl-error">{ __( 'No active module provides a page.', 'bw-fokus-log' ) }</p>;
	}

	const Page = current.component;
	const content = providers().reduceRight< JSX.Element >( ( inner, Provider ) => <Provider>{ inner }</Provider>, <Page /> );

	return (
		<div className="bwfl-shell">
			<header className="bwfl-head">
				<div className="bwfl-head-text">
					<div className="bwfl-kicker">{ __( 'Fokus-Log', 'bw-fokus-log' ) } · { date }</div>
					<h1 className="bwfl-title">{ __( 'My day', 'bw-fokus-log' ) }</h1>
					<p className="bwfl-sub">{ __( 'Open does not mean failed – it means: still on it.', 'bw-fokus-log' ) }</p>
				</div>
				<nav className="bwfl-tabs" aria-label={ __( 'View', 'bw-fokus-log' ) }>
					{ list.map( ( route ) => (
						<button key={ route.path } type="button" aria-pressed={ route.path === current.path } onClick={ () => go( route.path ) }>
							{ route.label }
						</button>
					) ) }
				</nav>
			</header>
			{ content }
		</div>
	);
}
