<?php
/**
 * REST routes of the planner.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Modules\Planner\Controller;

use BW\FokusLog\Modules\Planner\Service\DayService;
use BW\FokusLog\Modules\Planner\Service\HistoryService;
use BW\FokusLog\Modules\Planner\Service\PreferencesService;
use BW\FokusLog\Support\Dates;

defined( 'ABSPATH' ) || exit;

/**
 * `/wp-json/bw-fokus-log/v1/…`: every signed-in user sees only their own data.
 *
 * - GET  /day?date=YYYY-MM-DD
 * - POST /day {date, content}
 * - GET  /history?today=YYYY-MM-DD&days=14
 * - POST /settings {pause}
 */
final class RestController {

	public const NAMESPACE = 'bw-fokus-log/v1';

	/**
	 * Creates the controller.
	 *
	 * @param DayService         $days        Day rules.
	 * @param HistoryService     $history     History numbers.
	 * @param PreferencesService $preferences User preferences.
	 */
	public function __construct(
		private readonly DayService $days,
		private readonly HistoryService $history,
		private readonly PreferencesService $preferences
	) {}

	/**
	 * Hook the routes.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Declare the routes.
	 */
	public function register_routes(): void {
		$date = array(
			'required'          => true,
			'type'              => 'string',
			'validate_callback' => static fn ( $value ): bool => Dates::is_valid_day( $value ),
		);

		register_rest_route(
			self::NAMESPACE,
			'/day',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_day' ),
					'permission_callback' => array( $this, 'can_use' ),
					'args'                => array( 'date' => $date ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_day' ),
					'permission_callback' => array( $this, 'can_use' ),
					'args'                => array(
						'date'    => $date,
						'content' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/history',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_history' ),
				'permission_callback' => array( $this, 'can_use' ),
				'args'                => array(
					'today' => $date,
					'days'  => array(
						'type'    => 'integer',
						'default' => 14,
						'minimum' => 1,
						'maximum' => 366,
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_settings' ),
				'permission_callback' => array( $this, 'can_use' ),
				'args'                => array(
					'pause' => array(
						'required' => true,
						'type'     => 'integer',
						'minimum'  => 0,
						'maximum'  => PreferencesService::MAX_PAUSE,
					),
				),
			)
		);
	}

	/**
	 * Only signed-in users.
	 */
	public function can_use(): bool {
		return is_user_logged_in();
	}

	/**
	 * GET /day.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public function get_day( \WP_REST_Request $request ): \WP_REST_Response {
		return rest_ensure_response( $this->days->get( get_current_user_id(), (string) $request['date'] ) );
	}

	/**
	 * POST /day.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function save_day( \WP_REST_Request $request ) {
		$stats = $this->days->save(
			get_current_user_id(),
			(string) $request['date'],
			sanitize_textarea_field( (string) $request['content'] )
		);

		if ( null === $stats ) {
			return new \WP_Error( 'bw_fokus_log_too_long', __( 'The day plan is too long.', 'bw-fokus-log' ), array( 'status' => 400 ) );
		}

		return rest_ensure_response( array( 'saved' => true ) + $stats );
	}

	/**
	 * GET /history.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public function get_history( \WP_REST_Request $request ): \WP_REST_Response {
		return rest_ensure_response(
			$this->history->last_days( get_current_user_id(), (string) $request['today'], (int) $request['days'] )
		);
	}

	/**
	 * POST /settings.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public function save_settings( \WP_REST_Request $request ): \WP_REST_Response {
		return rest_ensure_response(
			array( 'pause' => $this->preferences->set_pause( get_current_user_id(), (int) $request['pause'] ) )
		);
	}
}
