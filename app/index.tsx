import { createRoot } from '@wordpress/element';
import { Shell } from './components/Shell';
import './modules/planner';
import './modules/coach';
import './style.css';

/**
 * Mount the app into every container the server printed.
 * Modules are imported above for their side effect: they register with the filters.
 */
document.querySelectorAll< HTMLElement >( '.bwfl-app' ).forEach( ( el ) => {
	el.replaceChildren();
	createRoot( el ).render( <Shell /> );
} );
