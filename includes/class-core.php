<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core Plugin Class
 *
 * @package Anik_Smart_TOC
 */

class Aniksmta_Core {

	/**
	 * Single instance
	 *
	 * @var Aniksmta_Core
	 */
	private static $instance = null;

	/**
	 * Settings instance
	 *
	 * @var Aniksmta_Settings
	 */
	public $settings;

	/**
	 * Get instance
	 *
	 * @return Aniksmta_Core
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
		require_once ANIKSMTA_PATH . 'includes/class-settings.php';
		require_once ANIKSMTA_PATH . 'includes/class-render.php';
		require_once ANIKSMTA_PATH . 'includes/class-shortcode.php';
		require_once ANIKSMTA_PATH . 'includes/class-admin.php';
	}

	/**
	 * Initialize components
	 */
	private function init() {
		$this->settings = new Aniksmta_Settings();

		// Initialize components
		new Aniksmta_Render( $this->settings );
		new Aniksmta_Shortcode( $this->settings );

		// Admin only
		if ( is_admin() ) {
			new Aniksmta_Admin();
		}
	}
}
