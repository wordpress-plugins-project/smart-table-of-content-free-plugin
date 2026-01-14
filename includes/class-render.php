<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * TOC Render Class
 *
 * @package Smart_TOC
 */

class Smart_TOC_Render {

    /**
     * Settings instance
     *
     * @var Smart_TOC_Settings
     */
    private $settings;

    /**
     * Track if TOC was rendered
     *
     * @var bool
     */
    private $toc_rendered = false;

    /**
     * Store generated TOC for reuse
     *
     * @var string
     */
    private $generated_toc = '';

    /**
     * Constructor
     *
     * @param Smart_TOC_Settings $settings Settings instance.
     */
    public function __construct( $settings ) {
        $this->settings = $settings;
        
        add_filter( 'the_content', array( $this, 'render' ), 20 );
        add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
        add_action( 'wp_footer', array( $this, 'output_inline_styles' ) );
    }

    /**
     * Conditionally enqueue assets
     */
    public function maybe_enqueue_assets() {
        if ( ! is_singular() ) {
            return;
        }

        if ( ! $this->settings->should_display() ) {
            return;
        }

        $this->enqueue_assets();
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'smart-toc-free',
            SMART_TOC_URL . 'assets/css/toc.css',
            array(),
            SMART_TOC_VERSION
        );

        wp_enqueue_script(
            'smart-toc-js',
            SMART_TOC_URL . 'assets/js/toc.js',
            array(),
            SMART_TOC_VERSION,
            true
        );

        wp_localize_script(
            'smart-toc-js',
            'smartTocSettings',
            array(
                'smoothScroll'    => $this->settings->get( 'smooth_scroll' ),
                'highlightActive' => $this->settings->get( 'highlight_active' ),
                'scrollOffset'    => $this->settings->get( 'scroll_offset', 80 ),
            )
        );
    }

    /**
     * Output inline styles for theme color
     */
    public function output_inline_styles() {
        if ( ! $this->toc_rendered ) {
            return;
        }

        $theme_color = $this->settings->get( 'theme_color' );
        if ( empty( $theme_color ) ) {
            return;
        }

        $hover_color = $this->adjust_brightness( $theme_color, -20 );
        ?>
        <style id="smart-toc-theme-styles">
            .smart-toc .toc-item > a { color: <?php echo esc_attr( $theme_color ); ?>; }
            .smart-toc .toc-item > a:hover { color: <?php echo esc_attr( $hover_color ); ?>; }
            .smart-toc .smart-toc-toggle { color: <?php echo esc_attr( $theme_color ); ?>; }
            .smart-toc .toc-item > a.active { 
                background: <?php echo esc_attr( $theme_color ); ?> !important;
                color: #fff !important;
            }
            .smart-toc-header { border-left-color: <?php echo esc_attr( $theme_color ); ?>; }
        </style>
        <?php
    }

    /**
     * Adjust color brightness
     *
     * @param string $hex Hex color.
     * @param int    $steps Steps to adjust.
     * @return string
     */
    private function adjust_brightness( $hex, $steps ) {
        $hex = ltrim( $hex, '#' );

        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        $r = max( 0, min( 255, $r + $steps ) );
        $g = max( 0, min( 255, $g + $steps ) );
        $b = max( 0, min( 255, $b + $steps ) );

        return sprintf( '#%02x%02x%02x', $r, $g, $b );
    }

    /**
     * Main render function
     *
     * @param string $content Post content.
     * @return string
     */
    public function render( $content ) {
        if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        if ( ! $this->settings->should_display() ) {
            return $content;
        }

        // Check position setting
        $position = $this->settings->get( 'position' );
        if ( 'manual' === $position ) {
            // Only modify headings, don't insert TOC
            return $this->process_content_headings( $content );
        }

        // Generate TOC
        $toc_data = $this->generate_toc( $content );
        
        if ( empty( $toc_data['toc'] ) ) {
            return $content;
        }

        $this->toc_rendered = true;
        $this->generated_toc = $toc_data['toc'];

        // Insert TOC based on position
        switch ( $position ) {
            case 'after_first_paragraph':
                $content = $this->insert_after_first_paragraph( $toc_data['content'], $toc_data['toc'] );
                break;
            case 'before_content':
            default:
                $content = $toc_data['toc'] . $toc_data['content'];
                break;
        }

        return $content;
    }

    /**
     * Process content headings without inserting TOC
     *
     * @param string $content Post content.
     * @return string
     */
    private function process_content_headings( $content ) {
        $toc_data = $this->generate_toc( $content );
        return $toc_data['content'];
    }

    /**
     * Generate TOC from content
     *
     * @param string $content Post content.
     * @param array  $overrides Setting overrides.
     * @return array
     */
    public function generate_toc( $content, $overrides = array() ) {
        $heading_levels = $this->settings->get( 'heading_levels' );
        $min_headings = $this->settings->get( 'min_headings' );
        $exclude_class = $this->settings->get( 'exclude_class' );

        // Build regex pattern for heading levels
        $levels = implode( '', $heading_levels );
        $pattern = '/<h([' . $levels . '])([^>]*)>(.*?)<\/h\1>/si';

        preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );

        // Filter out excluded headings
        $headings = array();
        foreach ( $matches as $match ) {
            if ( ! empty( $exclude_class ) && false !== strpos( $match[2], $exclude_class ) ) {
                continue;
            }
            $headings[] = $match;
        }

        // Check minimum headings
        if ( count( $headings ) < $min_headings ) {
            return array(
                'toc'     => '',
                'content' => $content,
            );
        }

        // Process headings and add IDs
        $toc_items = array();
        $processed_content = $content;
        $used_ids = array();

        foreach ( $headings as $index => $heading ) {
            $level = $heading[1];
            $attrs = $heading[2];
            $text = $heading[3];
            $clean_text = wp_strip_all_tags( $text );

            // Generate unique ID
            $id = $this->generate_heading_id( $clean_text, $used_ids );
            $used_ids[] = $id;

            // Check if heading already has an ID
            if ( preg_match( '/id=["\']([^"\']+)["\']/', $attrs, $id_match ) ) {
                $id = $id_match[1];
            } else {
                // Add ID to heading
                $new_heading = sprintf(
                    '<h%s id="%s"%s>%s</h%s>',
                    $level,
                    esc_attr( $id ),
                    $attrs,
                    $text,
                    $level
                );
                $processed_content = str_replace( $heading[0], $new_heading, $processed_content );
            }

            $toc_items[] = array(
                'level' => (int) $level,
                'text'  => $clean_text,
                'id'    => $id,
            );
        }

        // Build TOC HTML
        $toc_html = $this->build_toc_html( $toc_items, $overrides );

        return array(
            'toc'     => $toc_html,
            'content' => $processed_content,
        );
    }

    /**
     * Generate unique heading ID
     *
     * @param string $text Heading text.
     * @param array  $used_ids Already used IDs.
     * @return string
     */
    private function generate_heading_id( $text, $used_ids ) {
        $id = sanitize_title( $text );
        
        if ( empty( $id ) ) {
            $id = 'section';
        }

        // Ensure uniqueness
        $original_id = $id;
        $counter = 1;
        while ( in_array( $id, $used_ids, true ) ) {
            $id = $original_id . '-' . $counter;
            $counter++;
        }

        return $id;
    }

    /**
     * Build TOC HTML
     *
     * @param array $items TOC items.
     * @param array $overrides Setting overrides.
     * @return string
     */
    private function build_toc_html( $items, $overrides = array() ) {
        if ( empty( $items ) ) {
            return '';
        }

        $title = isset( $overrides['title'] ) ? $overrides['title'] : $this->settings->get_title();
        $collapsed = isset( $overrides['collapsed'] ) ? $overrides['collapsed'] : $this->settings->get( 'default_collapsed' );

        $collapsed_class = $collapsed ? ' collapsed' : '';
        $aria_expanded = $collapsed ? 'false' : 'true';

        $html = '<nav class="smart-toc' . esc_attr( $collapsed_class ) . '" aria-label="' . esc_attr__( 'Table of Contents', 'smart-toc-free' ) . '">';
        $html .= '<div class="smart-toc-header">';
        $html .= '<span class="smart-toc-title">' . esc_html( $title ) . '</span>';
        $html .= '<button class="smart-toc-toggle" aria-expanded="' . esc_attr( $aria_expanded ) . '" aria-label="' . esc_attr__( 'Toggle Table of Contents', 'smart-toc-free' ) . '">';
        $html .= '<span class="toggle-icon"></span>';
        $html .= '</button>';
        $html .= '</div>';

        $html .= '<div class="smart-toc-body">';
        $html .= $this->build_nested_list( $items );
        $html .= '</div>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Build nested list from flat items
     *
     * @param array $items TOC items.
     * @return string
     */
    private function build_nested_list( $items ) {
        if ( empty( $items ) ) {
            return '';
        }

        $show_numbers = (bool) $this->settings->get( 'show_numbers', false );
        $html = '<ul class="smart-toc-list">';
        $counter = 1;
        
        foreach ( $items as $item ) {
            $indent_class = 'toc-level-' . $item['level'];
            $number_html = $show_numbers ? '<span class="toc-number">' . esc_html( $counter ) . '.</span> ' : '';
            $html .= '<li class="toc-item ' . esc_attr( $indent_class ) . '">';
            $html .= '<a href="#' . esc_attr( $item['id'] ) . '">' . $number_html . esc_html( $item['text'] ) . '</a>';
            $html .= '</li>';
            $counter++;
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Insert TOC after first paragraph
     *
     * @param string $content Post content.
     * @param string $toc TOC HTML.
     * @return string
     */
    private function insert_after_first_paragraph( $content, $toc ) {
        $position = strpos( $content, '</p>' );
        
        if ( false !== $position ) {
            return substr_replace( $content, '</p>' . $toc, $position, 4 );
        }

        return $toc . $content;
    }

    /**
     * Generate TOC HTML for shortcode/widget
     *
     * @param string $content Post content.
     * @param array  $overrides Setting overrides.
     * @return string
     */
    public function generate_toc_html( $content, $overrides = array() ) {
        $toc_data = $this->generate_toc( $content, $overrides );
        
        if ( ! empty( $toc_data['toc'] ) ) {
            $this->toc_rendered = true;
            $this->enqueue_assets();
        }

        return $toc_data['toc'];
    }

    /**
     * Check if TOC was rendered
     *
     * @return bool
     */
    public function was_rendered() {
        return $this->toc_rendered;
    }

    /**
     * Get generated TOC
     *
     * @return string
     */
    public function get_generated_toc() {
        return $this->generated_toc;
    }
}
