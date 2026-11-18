import { __ } from '@wordpress/i18n';
import { extendList } from '../../lib/extend';
import { HistoryView } from './HistoryView';
import { TodayView } from './TodayView';
import { PlannerProvider } from './store';

/** Planner module: registers its pages and state with the app. */
extendList( 'bw-fokus-log.routes', 'bw-fokus-log/planner', { path: '/', label: __( 'Today', 'bw-fokus-log' ), component: TodayView, order: 10 } );
extendList( 'bw-fokus-log.routes', 'bw-fokus-log/planner-history', { path: '/history', label: __( 'History', 'bw-fokus-log' ), component: HistoryView, order: 20 } );
extendList( 'bw-fokus-log.providers', 'bw-fokus-log/planner', PlannerProvider );
