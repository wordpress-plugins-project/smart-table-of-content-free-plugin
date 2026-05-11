<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gutenberg Block Integration
 *
 * @package Anik_Smart_TOC
 */
class Aniksmta_Block {

	/**
	 * Settings instance.
	 *
	 * @var Aniksmta_Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Aniksmta_Settings $settings Settings instance.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Register block and editor script.
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'aniksmta-toc-block',
			ANIKSMTA_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ),
			ANIKSMTA_VERSION,
			true
		);

		register_block_type(
			'aniksmta/toc',
			array(
				'api_version'     => 2,
				'editor_script'   => 'aniksmta-toc-block',
				'render_callback' => array( $this, 'render_block' ),
				'attributes'      => array(
					'title'     => array(
						'type'    => 'string',
						'default' => '',
					),
					'collapsed' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);
	}

	/**
	 * Render block output on frontend/editor.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_block( $attributes ) {
		global $post;
		if ( ! ( $post instanceof WP_Post ) ) {
			return '';
		}

		$overrides = array();
		if ( isset( $attributes['title'] ) && '' !== $attributes['title'] ) {
			$overrides['title'] = sanitize_text_field( $attributes['title'] );
		}
		if ( isset( $attributes['collapsed'] ) ) {
			$overrides['collapsed'] = (bool) $attributes['collapsed'];
		}

		$content = preg_replace( '/\[aniksmta_toc[^\]]*\]/', '', $post->post_content );
		if ( ! is_string( $content ) ) {
			$content = $post->post_content;
		}
		$content = wptexturize( $content );
		$content = wpautop( $content );
		$content = do_shortcode( $content );

		$render = new Aniksmta_Render( $this->settings, true );
		return $render->generate_toc_html( $content, $overrides );
	}
}
