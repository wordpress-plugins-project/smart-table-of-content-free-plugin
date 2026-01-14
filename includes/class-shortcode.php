<?php
/**
 * Shortcode Handler
 *
 * @package Smart_TOC
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Smart_TOC_Shortcode {

    /**
     * Settings instance
     *
     * @var Smart_TOC_Settings
     */
    private $settings;

    /**
     * Constructor
     *
     * @param Smart_TOC_Settings $settings Settings instance.
     */
    public function __construct( $settings ) {
        $this->settings = $settings;
        
        add_shortcode( 'smart_toc', array( $this, 'render_shortcode' ) );
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
            'smart_toc'
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
        $content = preg_replace( '/\[smart_toc[^\]]*\]/', '', $content );
        
        // Process shortcodes and basic formatting without triggering the_content filter
        $content = do_shortcode( $content );
        $content = wptexturize( $content );
        $content = wpautop( $content );

        // Generate TOC
        $render = new Smart_TOC_Render( $this->settings );
        $toc_html = $render->generate_toc_html( $content, $overrides );

        return $toc_html;
    }
}
