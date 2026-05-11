<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings Handler
 *
 * @package Anik_Smart_TOC
 */

class Aniksmta_Settings {

	/**
	 * Option name
	 *
	 * @var string
	 */
	private $option_name = 'aniksmta_settings';

	/**
	 * Cached settings
	 *
	 * @var array|null
	 */
	private $settings = null;

	/**
	 * Default settings
	 *
	 * @var array
	 */
	private $defaults = array(
		'enabled'                       => true,
		'post_types'                    => array( 'post', 'page' ),
		'min_headings'                  => 2,
		'heading_levels'                => array( 2, 3, 4, 5, 6 ),
		'default_collapsed'             => true,
		'position'                      => 'before_content',
		'smooth_scroll'                 => true,
		'highlight_active'              => true,
		'title'                         => 'Table of Contents',
		'theme_color'                   => '#0073aa',
		'exclude_class'                 => 'no-toc',
		'scroll_offset'                 => 80,
		'show_numbers'                  => true,
		'counter_format'                => 'decimal',
		'toc_theme'                     => 'dark',
		'exclude_headings'              => '',
		'schema_enabled'                => true,
		'sticky_toc'                    => false,
		'sticky_position'               => 'inline',
		'sticky_width'                  => 280,
		'sticky_offset'                 => 20,
		'copy_link'                     => true,
		'reading_progress'              => true,
		'dynamic_content'               => true,
		'lazy_load_toc'                 => true,
		'mobile_toc_modal'              => false,
		'floating_desktop'              => true,
		'floating_toc_position'         => 'right',
		'floating_toc_style'            => 'icon_text',
		'floating_toc_theme'            => 'dark',
		'floating_toc_panel_width'      => 320,
		'floating_toc_auto_close'       => true,
		'floating_toc_show_progress'    => true,
		'floating_toc_default_expanded' => true,
		'collapsible_sections'          => true,
		'sections_collapsed'            => false,
		'reading_time'                  => true,
		'back_to_top'                   => false,
		'back_to_top_icon'              => 'arrow',
		'back_to_top_style'             => 'circle',
		'back_to_top_bg_color'          => '',
		'back_to_top_icon_color'        => '#ffffff',
		'back_to_top_show_desktop'      => true,
		'back_to_top_show_tablet'       => true,
		'back_to_top_show_mobile'       => true,
		'exclude_home'                  => true,
		'exclude_archive'               => true,
		'exclude_search'                => true,
		'exclude_404'                   => true,
		'auto_dark_mode'                => false,
		'toggle_icon_style'             => 'chevron',
	);

	/**
	 * Get all settings
	 *
	 * @return array
	 */
	public function get_all() {
		if ( null === $this->settings ) {
			$this->settings = get_option( $this->option_name, array() );
			$this->settings = wp_parse_args( $this->settings, $this->defaults );
		}
		return $this->settings;
	}

	/**
	 * Get a single setting
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		$settings = $this->get_all();

		if ( isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}

		if ( null !== $default ) {
			return $default;
		}

		return isset( $this->defaults[ $key ] ) ? $this->defaults[ $key ] : null;
	}

	/**
	 * Get TOC title
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->get( 'title', __( 'Table of Contents', 'anik-smart-table-of-contents' ) );
	}

	/**
	 * Update settings
	 *
	 * @param array $values Array of key => value pairs.
	 * @return bool
	 */
	public function update( $values ) {
		$settings = $this->get_all();
		foreach ( $values as $key => $value ) {
			$settings[ $key ] = $value;
		}
		$this->settings = $settings;
		return update_option( $this->option_name, $settings );
	}

	/**
	 * Check if TOC should display on current page
	 *
	 * @return bool
	 */
	public function should_display() {
		// Check if globally enabled
		if ( ! $this->get( 'enabled' ) ) {
			return false;
		}

		// Check page type exclusions
		if ( $this->get( 'exclude_home', true ) && ( is_front_page() || is_home() ) ) {
			return false;
		}
		if ( $this->get( 'exclude_archive', true ) && is_archive() ) {
			return false;
		}
		if ( $this->get( 'exclude_search', true ) && is_search() ) {
			return false;
		}
		if ( $this->get( 'exclude_404', true ) && is_404() ) {
			return false;
		}

		// Check post type
		$post_types        = $this->get( 'post_types' );
		$current_post_type = get_post_type();
		if ( ! in_array( $current_post_type, $post_types, true ) ) {
			return false;
		}

		// Check per-post disable
		$post_id = get_the_ID();
		if ( $post_id && get_post_meta( $post_id, '_aniksmta_disable', true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get default settings
	 *
	 * @return array
	 */
	public function get_defaults() {
		return $this->defaults;
	}
}
