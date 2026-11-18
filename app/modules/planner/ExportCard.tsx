import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { usePlanner } from './store';

/** Dark card with the plan in its text format. */
export function ExportCard(): JSX.Element {
	const { text, date, status } = usePlanner();
	const [ copied, setCopied ] = useState( false );
	const fileName = `day-plan-${ date }.txt`;

	const copy = () => {
		void navigator.clipboard?.writeText( text() ).then( () => {
			setCopied( true );
			window.setTimeout( () => setCopied( false ), 1800 );
		} );
	};

	const download = () => {
		const blob = new Blob( [ text() + '\n' ], { type: 'text/plain;charset=utf-8' } );
		const link = document.createElement( 'a' );
		link.href = URL.createObjectURL( blob );
		link.download = fileName;
		document.body.appendChild( link );
		link.click();
		window.setTimeout( () => {
			URL.revokeObjectURL( link.href );
			link.remove();
		}, 500 );
	};

	return (
		<section className="bwfl-card bwfl-dark" aria-labelledby="bwfl-file-h">
			<div className="bwfl-card-head">
				<h2 id="bwfl-file-h">{ __( 'Saved as text', 'bw-fokus-log' ) }</h2>
				<span className="bwfl-actions">
					<button type="button" className="bwfl-btn bwfl-btn-ghost" onClick={ copy }>
						{ copied ? __( 'Copied ✓', 'bw-fokus-log' ) : __( 'Copy', 'bw-fokus-log' ) }
					</button>
					<button type="button" className="bwfl-btn bwfl-btn-ghost" onClick={ download }>
						{ __( '.txt', 'bw-fokus-log' ) }
					</button>
				</span>
			</div>
			<div className="bwfl-file-meta">{ fileName } · { __( 'Format:', 'bw-fokus-log' ) } {'{start|end|duration}'}</div>
			<pre className="bwfl-export">{ text() }</pre>
			<p className={ `bwfl-status${ status.warn ? ' is-warn' : '' }` } role="status">{ status.text }</p>
		</section>
	);
}
