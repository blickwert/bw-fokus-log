<?php
/**
 * Coach texts for the app and the REST route to change them.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Modules\Coach\Controller;

use BW\FokusLog\Modules\Coach\Service\CoachService;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the texts to the app data and exposes `POST /coach` for administrators.
 */
final class CoachController {

	/**
	 * Creates the controller.
	 *
	 * @param CoachService $coach Coach texts.
	 */
	public function __construct( private readonly CoachService $coach ) {}

	/**
	 * Hook app data and route.
	 */
	public function register(): void {
		add_filter( 'bw_fokus_log_app_config', array( $this, 'add_config' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Declare the route.
	 */
	public function register_routes(): void {
		register_rest_route(
			'bw-fokus-log/v1',
			'/coach',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save' ),
				'permission_callback' => static fn (): bool => current_user_can( 'manage_options' ),
			)
		);
	}

	/**
	 * Give the app the current texts.
	 *
	 * @param array<string, mixed> $config App data.
	 * @return array<string, mixed>
	 */
	public function add_config( array $config ): array {
		$config['coach'] = $this->coach->get();

		return $config;
	}

	/**
	 * POST /coach.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public function save( \WP_REST_Request $request ): \WP_REST_Response {
		return rest_ensure_response( $this->coach->save( (array) $request->get_json_params() ) );
	}
}
