<?php
/**
 * Die App selbst: Menüpunkt „Fokus-Log“ im Backend + Shortcode [fokus_log] im Frontend.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Fokus_Log_App {

	public static function init() {
		add_shortcode( 'fokus_log', array( __CLASS__, 'shortcode' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
	}

	public static function menu() {
		add_menu_page( 'Fokus-Log', 'Fokus-Log', 'read', 'fokus-log', array( __CLASS__, 'admin_page' ), 'dashicons-clock', 3 );
	}

	public static function admin_page() {
		echo '<div class="wrap">' . self::render() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function shortcode() {
		if ( ! is_user_logged_in() ) {
			return sprintf(
				'<p class="fokus-log-login">Bitte <a href="%s">anmelden</a>, um deinen Tagesplan zu sehen.</p>',
				esc_url( wp_login_url( get_permalink() ) )
			);
		}
		return self::render();
	}

	public static function render() {
		self::enqueue();
		return '<div id="fokus-log-app" class="fokus-log" aria-live="off"><p class="fl-loading">Fokus-Log wird geladen …</p></div>';
	}

	public static function enqueue() {
		$user     = wp_get_current_user();
		$settings = Fokus_Log_Settings::get();

		wp_enqueue_style( 'fokus-log', FOKUS_LOG_URL . 'assets/app.css', array(), FOKUS_LOG_VERSION );
		wp_enqueue_script( 'fokus-log', FOKUS_LOG_URL . 'assets/app.js', array(), FOKUS_LOG_VERSION, true );

		$config = array(
			'restUrl' => esc_url_raw( rest_url( Fokus_Log_Rest::NS . '/' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'userId'  => (int) $user->ID,
			'name'    => $user->display_name,
			'pause'   => Fokus_Log_Settings::user_pause( $user->ID ),
			'quotes'  => array(
				'run'     => Fokus_Log_Settings::lines( $settings['run'] ),
				'titles'  => Fokus_Log_Settings::lines( $settings['titles'] ),
				'done'    => Fokus_Log_Settings::lines( $settings['done'] ),
				'allDone' => $settings['all_done'],
			),
		);

		wp_add_inline_script( 'fokus-log', 'window.FokusLogConfig = ' . wp_json_encode( $config ) . ';', 'before' );
	}
}
