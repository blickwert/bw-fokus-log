/**
 * Data the server hands to the app (see PHP `App::enqueue()`).
 */
export interface CoachTexts {
	run: string[];
	titles: string[];
	done: string[];
	allDone: string;
}

export interface AppConfig {
	context: 'admin' | 'site';
	userId: number;
	name: string;
	locale: string;
	restNs: string;
	canEdit: boolean;
	pause?: number;
	coach?: CoachTexts;
}

declare global {
	interface Window {
		bwFokusLog?: AppConfig;
	}
}

export const config: AppConfig = window.bwFokusLog ?? {
	context: 'site',
	userId: 0,
	name: '',
	locale: 'en-US',
	restNs: '/bw-fokus-log/v1',
	canEdit: false,
};
