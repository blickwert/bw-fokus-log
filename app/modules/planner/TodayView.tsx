import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { mins } from '../../lib/format';
import { BreakBanner } from './BreakBanner';
import { Celebration } from './Celebration';
import { ExportCard } from './ExportCard';
import { PlanEditor } from './PlanEditor';
import { SettingsCard } from './SettingsCard';
import { TodoItem } from './TodoItem';
import { usePlanner } from './store';

/** "Today": plan, progress, break and the side cards. */
export function TodayView(): JSX.Element {
	const planner = usePlanner();
	const { items, doneCount, focusSeconds, loaded, loadError } = planner;
	const [ editing, setEditing ] = useState< boolean | null >( null );

	if ( ! loaded ) {
		return <p className="bwfl-loading">{ __( 'Loading Fokus-Log …', 'bw-fokus-log' ) }</p>;
	}

	// Until the user chooses, an empty plan opens the editor.
	const isEditing = editing ?? items.length === 0;
	const total = items.length;

	return (
		<>
			{ loadError && <p className="bwfl-error">{ __( 'The server could not be reached – you are working with the local copy.', 'bw-fokus-log' ) }</p> }
			<div className="bwfl-grid">
				<section className="bwfl-card bwfl-main" aria-labelledby="bwfl-plan-h">
					<div className="bwfl-card-head">
						<h2 id="bwfl-plan-h">{ __( 'Day plan', 'bw-fokus-log' ) }</h2>
						{ ! isEditing && (
							<button type="button" className="bwfl-btn" onClick={ () => setEditing( true ) }>
								{ __( 'Edit plan', 'bw-fokus-log' ) }
							</button>
						) }
					</div>

					{ isEditing ? (
						<PlanEditor initial={ planner.text() } canCancel={ total > 0 } onClose={ () => setEditing( false ) } />
					) : (
						<>
							<div className="bwfl-progress">
								<div className="bwfl-progress-row">
									{ /* translators: 1: done to-dos, 2: all to-dos */ }
									<span>{ sprintf( __( '%1$d of %2$d done', 'bw-fokus-log' ), doneCount, total ) }</span>
									{ /* translators: %s: duration like "35 min" */ }
									<span>{ sprintf( __( '%s focus', 'bw-fokus-log' ), mins( focusSeconds ) ) }</span>
								</div>
								<div className="bwfl-bar">
									<div style={ { width: `${ total ? Math.round( ( doneCount / total ) * 100 ) : 0 }%` } } />
								</div>
							</div>
							<BreakBanner />
							<ul className="bwfl-list">
								{ items.map( ( item ) => (
									<TodoItem key={ item.id } item={ item } />
								) ) }
							</ul>
							<p className="bwfl-hint">
								{ __( 'Tap = start or pause. The tick on the right (appears on hover) ticks a to-do off – even without a timer.', 'bw-fokus-log' ) }
							</p>
						</>
					) }
				</section>

				<div className="bwfl-side">
					<SettingsCard />
					<ExportCard />
				</div>
			</div>
			<Celebration />
		</>
	);
}
