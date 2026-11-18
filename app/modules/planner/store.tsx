import { createContext, useCallback, useContext, useEffect, useReducer, useRef } from '@wordpress/element';
import type { ReactNode } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import { api } from '../../lib/api';
import { config } from '../../lib/config';
import { celebration, runQuote } from '../../lib/extend';
import { type Item, liveSeconds, parsePlan, serializePlan } from '../../lib/plan';
import { clock, dateKey, hm } from '../../lib/time';

export interface Party {
	title: string;
	text: string;
	confetti: { left: number; w: number; h: number; color: string; dur: string; delay: string }[];
}

interface Break {
	until: number;
	next: number;
	over: boolean;
}

interface Saved {
	text: string;
	saved: boolean;
	run: { id: number; title: string; runStart: number; acc: number } | null;
	brk: { until: number; nextTitle: string } | null;
}

export interface Planner {
	date: string;
	loaded: boolean;
	loadError: boolean;
	items: Item[];
	now: number;
	brk: Break | null;
	pause: number;
	status: { text: string; warn: boolean };
	party: Party | null;
	doneCount: number;
	focusSeconds: number;
	text: () => string;
	toggle: ( id: number ) => void;
	check: ( id: number ) => void;
	reopen: ( id: number ) => void;
	startNext: () => void;
	applyPlan: ( text: string ) => void;
	setPause: ( minutes: number ) => void;
	openFromYesterday: () => Promise< string[] | null >;
}

const Ctx = createContext< Planner | null >( null );

export const usePlanner = (): Planner => {
	const value = useContext( Ctx );
	if ( ! value ) {
		throw new Error( 'usePlanner needs the PlannerProvider.' );
	}
	return value;
};

const COLORS = [ '#F59E0B', '#DB2777', '#0F766E', '#6D28D9', '#1D4ED8', '#10B981' ];

function beep(): void {
	try {
		const Ctor = window.AudioContext ?? ( window as unknown as { webkitAudioContext?: typeof AudioContext } ).webkitAudioContext;
		if ( ! Ctor ) {
			return;
		}
		const ctx = new Ctor();
		const osc = ctx.createOscillator();
		const gain = ctx.createGain();
		osc.frequency.value = 660;
		osc.connect( gain );
		gain.connect( ctx.destination );
		gain.gain.setValueAtTime( 0.0001, ctx.currentTime );
		gain.gain.exponentialRampToValueAtTime( 0.2, ctx.currentTime + 0.02 );
		gain.gain.exponentialRampToValueAtTime( 0.0001, ctx.currentTime + 0.6 );
		osc.start();
		osc.stop( ctx.currentTime + 0.65 );
		osc.onended = () => void ctx.close();
	} catch {
		// No sound available.
	}
}

/**
 * Holds the day plan, timers, break and saving. The state lives in a ref and is
 * redrawn on every change and every second while something runs.
 */
export function PlannerProvider( { children }: { children?: ReactNode } ): JSX.Element {
	const [ , redraw ] = useReducer( ( n: number ) => n + 1, 0 );
	const date = useRef( dateKey( new Date() ) ).current;
	const localKey = `bwfokuslog:${ config.userId }:${ date }`;
	const originalTitle = useRef( document.title ).current;

	const s = useRef( {
		items: [] as Item[],
		brk: null as Break | null,
		loaded: false,
		loadError: false,
		pause: config.pause ?? 5,
		status: { text: '', warn: false },
		party: null as Party | null,
		lastSave: 0,
	} ).current;
	const saveTimer = useRef< number | undefined >( undefined );
	const partyTimer = useRef< number | undefined >( undefined );

	/* ---------- saving ---------- */

	const text = useCallback( () => serializePlan( s.items, Date.now() ), [ s ] );

	const persistLocal = useCallback(
		( saved: boolean ) => {
			const run = s.items.find( ( i ) => i.runStart );
			const next = s.brk ? s.items.find( ( i ) => i.id === s.brk?.next ) : undefined;
			const data: Saved = {
				text: text(),
				saved,
				run: run ? { id: run.id, title: run.title, runStart: run.runStart as number, acc: run.acc } : null,
				brk: s.brk && next ? { until: s.brk.until, nextTitle: next.title } : null,
			};
			try {
				window.localStorage.setItem( localKey, JSON.stringify( data ) );
			} catch {
				// Private mode: nothing to do.
			}
		},
		[ s, text, localKey ]
	);

	const saveNow = useCallback(
		( keepalive = false ): Promise< void > => {
			window.clearTimeout( saveTimer.current );
			s.lastSave = Date.now();
			s.status = { text: __( 'Saving …', 'bw-fokus-log' ), warn: false };
			redraw();
			return api( '/day', { method: 'POST', keepalive, body: { date, content: text() } } )
				.then( () => {
					persistLocal( true );
					s.status = { text: sprintf( /* translators: %s: clock time */ __( 'Saved at %s', 'bw-fokus-log' ), hm( Date.now() ) ), warn: false };
				} )
				.catch( () => {
					persistLocal( false );
					s.status = { text: __( 'Not saved – kept locally, will retry', 'bw-fokus-log' ), warn: true };
				} )
				.finally( redraw );
		},
		[ s, date, text, persistLocal ]
	);

	const scheduleSave = useCallback( () => {
		persistLocal( false );
		window.clearTimeout( saveTimer.current );
		saveTimer.current = window.setTimeout( () => void saveNow(), 600 );
	}, [ persistLocal, saveNow ] );

	/* ---------- actions ---------- */

	const stopAll = () => {
		s.items.forEach( ( i ) => {
			if ( i.runStart ) {
				i.acc = liveSeconds( i, Date.now() );
				i.runStart = null;
			}
		} );
	};

	const toggle = ( id: number ) => {
		const item = s.items.find( ( i ) => i.id === id );
		if ( ! item || item.done ) {
			return;
		}
		if ( item.runStart ) {
			item.acc = liveSeconds( item, Date.now() );
			item.runStart = null;
		} else {
			stopAll();
			const now = Date.now();
			item.runStart = now;
			item.start = item.start || hm( now );
			item.quote = runQuote( item.title );
			s.brk = null;
		}
		redraw();
		scheduleSave();
	};

	const showParty = ( item: Item ) => {
		const done = s.items.filter( ( i ) => i.done ).length;
		const made = celebration( { title: item.title, seconds: item.acc, done, total: s.items.length } );
		if ( ! made ) {
			return;
		}
		s.party = {
			...made,
			confetti: Array.from( { length: 28 }, ( _, k ) => ( {
				left: Math.round( Math.random() * 100 ),
				w: 6 + Math.round( Math.random() * 6 ),
				h: 10 + Math.round( Math.random() * 8 ),
				color: COLORS[ k % COLORS.length ],
				dur: ( 1.6 + Math.random() * 1.2 ).toFixed( 2 ),
				delay: ( Math.random() * 0.5 ).toFixed( 2 ),
			} ) ),
		};
		window.clearTimeout( partyTimer.current );
		partyTimer.current = window.setTimeout( () => {
			s.party = null;
			redraw();
		}, 3600 );
	};

	const check = ( id: number ) => {
		const idx = s.items.findIndex( ( i ) => i.id === id );
		const item = s.items[ idx ];
		if ( ! item || item.done ) {
			return;
		}
		const now = Date.now();
		item.acc = liveSeconds( item, now );
		item.runStart = null;
		item.done = true;
		item.start = item.start || hm( now );
		item.end = hm( now );
		item.quote = '';

		const next = s.items.slice( idx + 1 ).find( ( i ) => ! i.done ) ?? s.items.find( ( i ) => ! i.done );
		s.brk = next && s.pause > 0 ? { until: now + s.pause * 60000, next: next.id, over: false } : null;

		showParty( item );
		redraw();
		void saveNow();
	};

	const reopen = ( id: number ) => {
		const item = s.items.find( ( i ) => i.id === id );
		if ( ! item || ! item.done ) {
			return;
		}
		item.done = false;
		item.end = '';
		redraw();
		scheduleSave();
	};

	const startNext = () => {
		if ( s.brk ) {
			toggle( s.brk.next );
		}
	};

	const applyPlan = ( planText: string ) => {
		const running = s.items.find( ( i ) => i.runStart );
		s.items = parsePlan( planText );
		if ( running ) {
			const same = s.items.find( ( i ) => ! i.done && i.title === running.title );
			if ( same ) {
				same.acc = running.acc;
				same.runStart = running.runStart;
				same.start = running.start;
				same.quote = running.quote;
			}
		}
		s.brk = null;
		redraw();
		void saveNow();
	};

	const setPause = ( minutes: number ) => {
		s.pause = Math.max( 0, Math.min( 120, Math.round( minutes ) || 0 ) );
		redraw();
		api( '/settings', { method: 'POST', body: { pause: s.pause } } ).catch( () => undefined );
	};

	const openFromYesterday = (): Promise< string[] | null > => {
		const d = new Date();
		d.setDate( d.getDate() - 1 );
		return api< { content: string } >( '/day', { params: { date: dateKey( d ) } } )
			.then( ( r ) => parsePlan( r.content ).filter( ( i ) => ! i.done ).map( ( i ) => i.title ) )
			.catch( () => null );
	};

	/* ---------- load ---------- */

	useEffect( () => {
		let local: Saved | null = null;
		try {
			local = JSON.parse( window.localStorage.getItem( localKey ) || 'null' ) as Saved | null;
		} catch {
			local = null;
		}

		api< { content: string } >( '/day', { params: { date } } )
			.then( ( r ) => r.content || '' )
			.catch( () => {
				s.loadError = true;
				return '';
			} )
			.then( ( fetched ) => {
				const content = local && local.text && ( local.saved === false || ! fetched ) ? local.text : fetched;
				s.items = parsePlan( content );

				if ( local?.run ) {
					const run = local.run;
					const item = s.items.find( ( i ) => ! i.done && i.title === run.title );
					if ( item ) {
						item.acc = run.acc;
						item.runStart = run.runStart;
						item.quote = runQuote( item.title );
					}
				}
				if ( local?.brk && local.brk.until > Date.now() - 30 * 60000 ) {
					const next = s.items.find( ( i ) => ! i.done && i.title === local?.brk?.nextTitle );
					if ( next ) {
						s.brk = { until: local.brk.until, next: next.id, over: local.brk.until <= Date.now() };
					}
				}
				s.loaded = true;
				redraw();
				if ( local && local.saved === false && ! s.loadError ) {
					void saveNow();
				}
			} );

		const onHide = () => {
			if ( s.loaded && s.items.length ) {
				persistLocal( false );
				void saveNow( true );
			}
		};
		window.addEventListener( 'pagehide', onHide );
		return () => window.removeEventListener( 'pagehide', onHide );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	/* ---------- one-second tick ---------- */

	useEffect( () => {
		const id = window.setInterval( () => {
			if ( ! s.loaded ) {
				return;
			}
			const now = Date.now();
			const running = s.items.find( ( i ) => i.runStart );

			if ( running ) {
				document.title = `▶ ${ clock( liveSeconds( running, now ) ) } · ${ running.title }`;
				if ( now - s.lastSave > 60000 ) {
					void saveNow();
				}
				redraw();
			} else if ( s.brk ) {
				const left = ( s.brk.until - now ) / 1000;
				document.title = left > 0 ? sprintf( /* translators: %s: remaining time */ __( 'Break %s', 'bw-fokus-log' ), clock( left ) ) : __( 'Break over!', 'bw-fokus-log' );
				if ( left <= 0 && ! s.brk.over ) {
					s.brk.over = true;
					beep();
				}
				redraw();
			} else if ( document.title !== originalTitle ) {
				document.title = originalTitle;
			}
		}, 1000 );

		return () => {
			window.clearInterval( id );
			document.title = originalTitle;
		};
	}, [ s, saveNow, originalTitle ] );

	const now = Date.now();
	const value: Planner = {
		date,
		loaded: s.loaded,
		loadError: s.loadError,
		items: s.items,
		now,
		brk: s.brk,
		pause: s.pause,
		status: s.status,
		party: s.party,
		doneCount: s.items.filter( ( i ) => i.done ).length,
		focusSeconds: s.items.reduce( ( a, i ) => a + liveSeconds( i, now ), 0 ),
		text,
		toggle,
		check,
		reopen,
		startNext,
		applyPlan,
		setPause,
		openFromYesterday,
	};

	return <Ctx.Provider value={ value }>{ children }</Ctx.Provider>;
}
