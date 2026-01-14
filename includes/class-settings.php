<?php
/**
 * Settings Handler
 *
 * @package Smart_TOC
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Smart_TOC_Settings {

    /**
     * Option name
     *
     * @var string
     */
    private $option_name = 'smart_toc_settings';

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
        'enabled'           => true,
        'post_types'        => array( 'post', 'page' ),
        'min_headings'      => 2,
        'heading_levels'    => array( 2, 3, 4, 5, 6 ),
        'default_collapsed' => false,
        'position'          => 'before_content',
        'smooth_scroll'     => true,
        'highlight_active'  => true,
        'title'             => 'Table of Contents',
        'theme_color'       => '#0073aa',
        'exclude_class'     => 'no-toc',
        'scroll_offset'     => 80,
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
        return $this->get( 'title', __( 'Table of Contents', 'smart-toc' ) );
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

        // Check post type
        $post_types = $this->get( 'post_types' );
        if ( ! is_singular( $post_types ) ) {
            return false;
        }

        // Check per-post disable
        $post_id = get_the_ID();
        if ( get_post_meta( $post_id, '_smart_toc_disable', true ) ) {
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
