import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { usePlanner } from './store';

/** Text area for the day plan, one line per to-do. */
export function PlanEditor( { initial, onClose, canCancel }: { initial: string; onClose: () => void; canCancel: boolean } ): JSX.Element {
	const { applyPlan, openFromYesterday } = usePlanner();
	const [ value, setValue ] = useState( initial );
	const [ hint, setHint ] = useState( '' );

	const apply = () => {
		applyPlan( value );
		onClose();
	};

	const fetchYesterday = () => {
		void openFromYesterday().then( ( titles ) => {
			if ( titles === null ) {
				setHint( __( 'Yesterday could not be loaded.', 'bw-fokus-log' ) );
			} else if ( titles.length === 0 ) {
				setHint( __( 'Everything was done yesterday – nothing to carry over.', 'bw-fokus-log' ) );
			} else {
				setValue( ( v ) => ( v.trim() ? v.replace( /\s+$/, '' ) + '\n' : '' ) + titles.map( ( t ) => `- ${ t }` ).join( '\n' ) );
				/* translators: %d: number of to-dos */
				setHint( sprintf( __( '%d open to-dos carried over from yesterday.', 'bw-fokus-log' ), titles.length ) );
			}
		} );
	};

	return (
		<div className="bwfl-edit">
			<label htmlFor="bwfl-plan-text">
				{ __( 'One line per to-do, starting with "- ". Lines that already have {start|end|duration} are kept.', 'bw-fokus-log' ) }
			</label>
			<textarea
				id="bwfl-plan-text"
				rows={ 14 }
				value={ value }
				placeholder={ '- Go for a walk\n- Make the bed\n- Open the mail' }
				onChange={ ( e ) => setValue( e.target.value ) }
				onKeyDown={ ( e ) => {
					if ( e.key === 'Enter' && ( e.ctrlKey || e.metaKey ) ) {
						e.preventDefault();
						apply();
					}
				} }
			/>
			<div className="bwfl-actions">
				<button type="button" className="bwfl-btn bwfl-btn-primary" onClick={ apply }>
					{ __( 'Apply plan', 'bw-fokus-log' ) }
				</button>
				<button type="button" className="bwfl-btn" onClick={ fetchYesterday }>
					{ __( 'Get open ones from yesterday', 'bw-fokus-log' ) }
				</button>
				{ canCancel && (
					<button type="button" className="bwfl-btn" onClick={ onClose }>
						{ __( 'Cancel', 'bw-fokus-log' ) }
					</button>
				) }
			</div>
			<p className="bwfl-hint" role="status">{ hint }</p>
		</div>
	);
}
