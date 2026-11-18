import { __ } from '@wordpress/i18n';
import { settingsPanels } from '../../lib/extend';
import { usePlanner } from './store';

/** Settings card: break length, plus panels added by other modules. */
export function SettingsCard(): JSX.Element {
	const { pause, setPause } = usePlanner();
	const panels = settingsPanels();

	return (
		<section className="bwfl-card" aria-labelledby="bwfl-set-h">
			<h2 id="bwfl-set-h">{ __( 'Settings', 'bw-fokus-log' ) }</h2>
			<div className="bwfl-setting">
				<label htmlFor="bwfl-pause">{ __( 'Break between to-dos', 'bw-fokus-log' ) }</label>
				<span className="bwfl-setting-field">
					<input
						id="bwfl-pause"
						type="number"
						min={ 0 }
						max={ 120 }
						value={ pause }
						onChange={ ( e ) => setPause( parseInt( e.target.value, 10 ) ) }
					/>
					<span>{ __( 'min', 'bw-fokus-log' ) }</span>
				</span>
			</div>
			<p className="bwfl-hint">{ __( 'The break has a fixed end – so 5 minutes of phone never turn into an hour.', 'bw-fokus-log' ) }</p>
			{ panels.map( ( { id, component: Panel } ) => (
				<Panel key={ id } />
			) ) }
		</section>
	);
}
