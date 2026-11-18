import apiFetch from '@wordpress/api-fetch';
import { config } from './config';

interface Options {
	method?: 'GET' | 'POST';
	params?: Record<string, string | number>;
	body?: unknown;
	keepalive?: boolean;
}

/**
 * Call a route of this plugin. Root URL and nonce come from the `wp-api-fetch` middleware.
 */
export function api< T >( route: string, options: Options = {} ): Promise< T > {
	const query = options.params
		? '?' + new URLSearchParams( Object.entries( options.params ).map( ( [ k, v ] ) => [ k, String( v ) ] ) ).toString()
		: '';

	return ( apiFetch as ( o: unknown ) => Promise< T > )( {
		path: config.restNs + route + query,
		method: options.method ?? 'GET',
		data: options.body,
		keepalive: options.keepalive,
	} );
}
