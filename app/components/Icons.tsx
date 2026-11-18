/** Inline icons from the mockup. */
const base = { fill: 'none', 'aria-hidden': true, viewBox: '0 0 24 24' } as const;

export const CheckIcon = ( { size = 16, width = 3 }: { size?: number; width?: number } ) => (
	<svg { ...base } width={ size } height={ size } stroke="currentColor" strokeWidth={ width } strokeLinecap="round" strokeLinejoin="round">
		<path d="M5 12l5 5L20 7" />
	</svg>
);

export const PauseIcon = () => (
	<svg { ...base } width="14" height="14" stroke="currentColor" strokeWidth="3" strokeLinecap="round">
		<path d="M8 5v14M16 5v14" />
	</svg>
);

export const PlayIcon = () => (
	<svg { ...base } width="12" height="12" stroke="currentColor" strokeWidth="3" strokeLinejoin="round">
		<path d="M7 4l13 8-13 8z" />
	</svg>
);

export const SparkIcon = () => (
	<svg { ...base } width="18" height="18" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="bwfl-spark">
		<path d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8z" />
		<path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z" />
	</svg>
);

export const PartyIcon = () => (
	<svg width="64" height="64" viewBox="0 0 64 64" fill="none" aria-hidden="true" className="bwfl-party-icon">
		<path d="M8 56L21 21l22 22z" fill="#F59E0B" stroke="#9A3412" strokeWidth="2" strokeLinejoin="round" />
		<path d="M14 40l10 10M18 30l16 16" stroke="#9A3412" strokeWidth="2" strokeLinecap="round" />
		<path d="M29 17c2-6 8-6 8-12" stroke="#0F766E" strokeWidth="3" strokeLinecap="round" />
		<path d="M47 27c6-2 8 4 14 2" stroke="#6D28D9" strokeWidth="3" strokeLinecap="round" />
		<path d="M38 22l10-10" stroke="#DB2777" strokeWidth="3" strokeLinecap="round" />
		<circle cx="48" cy="6" r="3" fill="#DB2777" />
		<circle cx="58" cy="40" r="3" fill="#1D4ED8" />
		<circle cx="36" cy="30" r="2.5" fill="#10B981" />
		<path d="M56 10l1.5 3 3 1.5-3 1.5-1.5 3-1.5-3-3-1.5 3-1.5z" fill="#F59E0B" />
	</svg>
);
