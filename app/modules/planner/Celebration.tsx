import { PartyIcon } from '../../components/Icons';
import { usePlanner } from './store';

/** Confetti and the party card when a to-do is ticked off. */
export function Celebration(): JSX.Element | null {
	const { party } = usePlanner();

	if ( ! party ) {
		return null;
	}

	return (
		<div className="bwfl-party" role="status" aria-live="polite">
			{ party.confetti.map( ( c, k ) => (
				<span
					key={ k }
					className="bwfl-confetti"
					style={ { left: `${ c.left }%`, width: c.w, height: c.h, background: c.color, animationDuration: `${ c.dur }s`, animationDelay: `${ c.delay }s` } }
				/>
			) ) }
			<div className="bwfl-party-card">
				<PartyIcon />
				<div>
					<div className="bwfl-party-title">{ party.title }</div>
					<div className="bwfl-party-text">{ party.text }</div>
				</div>
			</div>
		</div>
	);
}
