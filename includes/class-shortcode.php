<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode Handler
 *
 * @package Anik_Smart_TOC
 */

class Aniksmta_Shortcode {

	/**
	 * Settings instance
	 *
	 * @var Aniksmta_Settings
	 */
	private $settings;

	/**
	 * Constructor
	 *
	 * @param Aniksmta_Settings $settings Settings instance.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;

		add_shortcode( 'aniksmta_toc', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'title'     => '',
				'collapsed' => '',
			),
			$atts,
			'aniksmta_toc'
		);

		// Check if we're on a singular page
		if ( ! is_singular() ) {
			return '';
		}

		// Get current post content
		global $post;
		if ( ! $post ) {
			return '';
		}

		// Build overrides from shortcode attributes
		$overrides = array();

		if ( ! empty( $atts['title'] ) ) {
			$overrides['title'] = sanitize_text_field( $atts['title'] );
		}

		if ( '' !== $atts['collapsed'] ) {
			$overrides['collapsed'] = filter_var( $atts['collapsed'], FILTER_VALIDATE_BOOLEAN );
		}

		// Get content
		$content = $post->post_content;

		// Remove our shortcode from content to prevent infinite loop
		$content = preg_replace( '/\[aniksmta_toc[^\]]*\]/', '', $content );

		// Process content formatting in correct WordPress order.
		$content = wptexturize( $content );
		$content = wpautop( $content );
		$content = do_shortcode( $content );

		// Generate TOC using a standalone generator to avoid duplicate hooks.
		$render   = new Aniksmta_Render( $this->settings, true );
		$toc_html = $render->generate_toc_html( $content, $overrides );

		return $toc_html;
	}
}
