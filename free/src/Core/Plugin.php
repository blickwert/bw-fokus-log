<?php
/**
 * Plugin entry point.
 *
 * @package BW\FokusLog
 */

declare(strict_types=1);

namespace BW\FokusLog\Core;

use BW\FokusLog\Core\Admin\AppPage;

defined( 'ABSPATH' ) || exit;

/**
 * Boots the plugin. The only singleton.
 */
final class Plugin {

	/**
	 * The shared instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Finds and registers the modules.
	 *
	 * @var ModuleManager
	 */
	private ModuleManager $modules;

	/**
	 * Returns the shared instance.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Wires the core services.
	 */
	private function __construct() {
		$this->modules = new ModuleManager( new Settings() );
	}

	/**
	 * Module manager, for add-ons and tests.
	 */
	public function modules(): ModuleManager {
		return $this->modules;
	}

	/**
	 * Hook everything into WordPress.
	 */
	public function boot(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this->modules, 'boot' ), 5 );

		( new AppPage() )->register();
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'bw-fokus-log', false, dirname( plugin_basename( BW_FOKUS_LOG_FILE ) ) . '/languages' );
	}

	/**
	 * Activation hook: let the modules create their tables.
	 */
	public static function activate(): void {
		self::instance()->modules->activate();
	}
}
