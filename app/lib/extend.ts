import { addFilter, applyFilters } from '@wordpress/hooks';
import type { ComponentType } from '@wordpress/element';

/**
 * Extension points. Modules register with `addFilter()` instead of importing each other.
 */
export interface Route {
	path: string;
	label: string;
	component: ComponentType;
	order: number;
}

export interface Celebration {
	title: string;
	text: string;
}

export interface SettingsPanel {
	id: string;
	component: ComponentType;
}

export const routes = (): Route[] => ( applyFilters( 'bw-fokus-log.routes', [] ) as Route[] ).sort( ( a, b ) => a.order - b.order );
export const providers = (): ComponentType< { children?: unknown } >[] =>
	applyFilters( 'bw-fokus-log.providers', [] ) as ComponentType< { children?: unknown } >[];
export const settingsPanels = (): SettingsPanel[] => applyFilters( 'bw-fokus-log.settings-panels', [] ) as SettingsPanel[];
export const runQuote = ( title: string ): string => applyFilters( 'bw-fokus-log.run-quote', '', title ) as string;
export const celebration = ( data: { title: string; seconds: number; done: number; total: number } ): Celebration | null =>
	applyFilters( 'bw-fokus-log.celebration', null, data ) as Celebration | null;

/** Typed helper to add an item to a list filter. */
export function extendList< T >( hook: string, namespace: string, item: T ): void {
	addFilter( hook, namespace, ( list: unknown ) => [ ...( list as T[] ), item ] );
}
