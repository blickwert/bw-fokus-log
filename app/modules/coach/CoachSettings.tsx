import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { api } from '../../lib/api';
import { type CoachTexts, config } from '../../lib/config';

const lines = ( list: string[] ): string => list.join( '\n' );
const split = ( text: string ): string[] => text.split( /\r\n|\r|\n/ ).map( ( l ) => l.trim() ).filter( Boolean );

/** Administrators can replace the coach sayings (one per line). */
export function CoachSettings(): JSX.Element {
	const texts = config.coach as CoachTexts;
	const [ run, setRun ] = useState( lines( texts.run ) );
	const [ titles, setTitles ] = useState( lines( texts.titles ) );
	const [ done, setDone ] = useState( lines( texts.done ) );
	const [ allDone, setAllDone ] = useState( texts.allDone );
	const [ message, setMessage ] = useState( '' );

	const save = () => {
		setMessage( '' );
		api< CoachTexts >( '/coach', { method: 'POST', body: { run: split( run ), titles: split( titles ), done: split( done ), allDone } } )
			.then( ( saved ) => {
				config.coach = saved;
				setMessage( __( 'Saved.', 'bw-fokus-log' ) );
			} )
			.catch( () => setMessage( __( 'Could not be saved.', 'bw-fokus-log' ) ) );
	};

	return (
		<details className="bwfl-coach-settings">
			<summary>{ __( 'Adjust coach sayings', 'bw-fokus-log' ) }</summary>
			<p className="bwfl-hint">
				{ __( 'One saying per line. Placeholders: {t} to-do, {d} duration, {n} done, {total} all. Empty fields restore the defaults.', 'bw-fokus-log' ) }
			</p>
			<label htmlFor="bwfl-coach-run">{ __( 'While you work', 'bw-fokus-log' ) }</label>
			<textarea id="bwfl-coach-run" rows={ 6 } value={ run } onChange={ ( e ) => setRun( e.target.value ) } />
			<label htmlFor="bwfl-coach-titles">{ __( 'Headlines when ticking off', 'bw-fokus-log' ) }</label>
			<textarea id="bwfl-coach-titles" rows={ 3 } value={ titles } onChange={ ( e ) => setTitles( e.target.value ) } />
			<label htmlFor="bwfl-coach-done">{ __( 'Sayings when ticking off', 'bw-fokus-log' ) }</label>
			<textarea id="bwfl-coach-done" rows={ 6 } value={ done } onChange={ ( e ) => setDone( e.target.value ) } />
			<label htmlFor="bwfl-coach-all">{ __( 'Saying when everything is done', 'bw-fokus-log' ) }</label>
			<input id="bwfl-coach-all" type="text" value={ allDone } onChange={ ( e ) => setAllDone( e.target.value ) } />
			<div className="bwfl-actions">
				<button type="button" className="bwfl-btn" onClick={ save }>
					{ __( 'Save sayings', 'bw-fokus-log' ) }
				</button>
				<span role="status">{ message }</span>
			</div>
		</details>
	);
}
