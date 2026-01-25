<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core Plugin Class
 *
 * @package Smart_TOC
 */

class Smart_TOC_Core {

	/**
	 * Single instance
	 *
	 * @var Smart_TOC_Core
	 */
	private static $instance = null;

	/**
	 * Settings instance
	 *
	 * @var Smart_TOC_Settings
	 */
	public $settings;

	/**
	 * Get instance
	 *
	 * @return Smart_TOC_Core
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init();
	}

	/**
	 * Load required files
	 */
	private function load_dependencies() {
		require_once SMART_TOC_PATH . 'includes/class-settings.php';
		require_once SMART_TOC_PATH . 'includes/class-render.php';
		require_once SMART_TOC_PATH . 'includes/class-shortcode.php';
		require_once SMART_TOC_PATH . 'includes/class-admin.php';
	}

	/**
	 * Initialize components
	 */
	private function init() {
		$this->settings = new Smart_TOC_Settings();

		// Initialize components
		new Smart_TOC_Render( $this->settings );
		new Smart_TOC_Shortcode( $this->settings );

		// Admin only
		if ( is_admin() ) {
			new Smart_TOC_Admin();
		}
	}
}
