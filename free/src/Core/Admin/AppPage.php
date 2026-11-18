<?php
/**
 * The "Fokus-Log" menu item in the WordPress admin.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Core\Admin;

use BW\FokusLog\Core\App;

defined( 'ABSPATH' ) || exit;

/**
 * One menu page; the app draws its sub pages itself (`&path=/…`).
 */
final class AppPage {

	public const SLUG = 'bw-fokus-log';

	/**
	 * Hook the menu.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * Add the menu entry.
	 */
	public function add_menu(): void {
		add_menu_page(
			__( 'Fokus-Log', 'bw-fokus-log' ),
			__( 'Fokus-Log', 'bw-fokus-log' ),
			'read',
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-clock',
			3
		);
	}

	/**
	 * Print the page.
	 */
	public function render(): void {
		echo '<div class="wrap">' . App::mount( 'admin' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the template.
	}
}
