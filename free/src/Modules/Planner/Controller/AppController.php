<?php
/**
 * Block and shortcode that show the app on the website.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Modules\Planner\Controller;

use BW\FokusLog\Core\App;
use BW\FokusLog\Modules\Planner\Service\PreferencesService;

defined( 'ABSPATH' ) || exit;

/**
 * `[bw_fokus_log]` and the block `bw-fokus-log/planner`.
 */
final class AppController {

	/**
	 * Creates the controller.
	 *
	 * @param PreferencesService $preferences User preferences.
	 */
	public function __construct( private readonly PreferencesService $preferences ) {}

	/**
	 * Hook shortcode, block and app data.
	 */
	public function register(): void {
		add_shortcode( 'bw_fokus_log', array( $this, 'render' ) );
		add_action( 'init', array( $this, 'register_block' ) );
		add_filter( 'bw_fokus_log_app_config', array( $this, 'add_config' ) );
	}

	/**
	 * Register the block, which renders on the server.
	 */
	public function register_block(): void {
		register_block_type(
			BW_FOKUS_LOG_DIR . 'blocks/planner',
			array( 'render_callback' => array( $this, 'render' ) )
		);
	}

	/**
	 * Output of the shortcode and the block.
	 */
	public function render(): string {
		if ( ! is_user_logged_in() ) {
			return sprintf(
				'<p class="bwfl-login">%s</p>',
				sprintf(
					/* translators: %s: link to the login page */
					esc_html__( 'Please %s to see your day plan.', 'bw-fokus-log' ),
					'<a href="' . esc_url( wp_login_url( (string) get_permalink() ) ) . '">' . esc_html__( 'sign in', 'bw-fokus-log' ) . '</a>'
				)
			);
		}

		return App::mount( 'site' );
	}

	/**
	 * Tell the app the user's break length.
	 *
	 * @param array<string, mixed> $config App data.
	 * @return array<string, mixed>
	 */
	public function add_config( array $config ): array {
		$config['pause'] = $this->preferences->pause( get_current_user_id() );

		return $config;
	}
}
