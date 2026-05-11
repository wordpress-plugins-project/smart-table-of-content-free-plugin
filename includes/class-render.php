<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TOC Render Class
 *
 * @package Anik_Smart_TOC
 */

class Aniksmta_Render {

	/**
	 * Settings instance
	 *
	 * @var Aniksmta_Settings
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
	 * @param Aniksmta_Settings $settings   Settings instance.
	 * @param bool              $skip_hooks Whether to skip registering hooks (used for shortcode rendering).
	 */
	public function __construct( $settings, $skip_hooks = false ) {
		$this->settings = $settings;

		if ( ! $skip_hooks ) {
			add_filter( 'the_content', array( $this, 'render' ), 20 );
			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
		}
	}

	/**
	 * Conditionally enqueue assets
	 */
	public function maybe_enqueue_assets() {
		if ( ! $this->settings->get( 'enabled' ) ) {
			return;
		}

		// Respect context-based exclusions before loading frontend assets.
		if ( $this->settings->get( 'exclude_home', true ) && ( is_front_page() || is_home() ) ) {
			return;
		}
		if ( $this->settings->get( 'exclude_archive', true ) && is_archive() ) {
			return;
		}
		if ( $this->settings->get( 'exclude_search', true ) && is_search() ) {
			return;
		}
		if ( $this->settings->get( 'exclude_404', true ) && is_404() ) {
			return;
		}

		// On singular content we can cheaply verify post-specific eligibility.
		if ( is_singular() ) {
			$post_id    = get_queried_object_id();
			$post_type  = $post_id ? get_post_type( $post_id ) : '';
			$post_types = $this->settings->get( 'post_types' );

			if ( $post_type && ! in_array( $post_type, $post_types, true ) ) {
				return;
			}

			if ( $post_id && get_post_meta( $post_id, '_aniksmta_disable', true ) ) {
				return;
			}
		}

		$this->enqueue_assets();
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'aniksmta-toc',
			ANIKSMTA_URL . 'assets/css/toc.css',
			array(),
			ANIKSMTA_VERSION
		);

		// Add dynamic theme color styles.
		$theme_color = sanitize_hex_color( $this->settings->get( 'theme_color' ) );
		if ( ! empty( $theme_color ) ) {
			$is_light_color       = $this->is_light_color( $theme_color );
			$accent_color         = $is_light_color ? $this->adjust_brightness( $theme_color, -170 ) : $theme_color;
			$hover_color          = $this->adjust_brightness( $accent_color, -20 );
			$active_bg_color      = $is_light_color ? $this->adjust_brightness( $theme_color, -20 ) : $theme_color;
			$active_text_color    = $this->get_contrast_text_color( $active_bg_color );
			$active_border_color  = $is_light_color ? $this->adjust_brightness( $theme_color, -90 ) : $hover_color;
			$active_outline_color = $is_light_color ? '#d5d9dd' : $this->adjust_brightness( $theme_color, -40 );

			$inline_css  = '.smart-toc .toc-item > a, .smart-toc .toc-item > .toc-item-inner > a { color: ' . esc_attr( $accent_color ) . '; }';
			$inline_css .= '.smart-toc .toc-item > a:hover, .smart-toc .toc-item > .toc-item-inner > a:hover { color: ' . esc_attr( $hover_color ) . '; }';
			$inline_css .= '.smart-toc .smart-toc-toggle { color: ' . esc_attr( $accent_color ) . '; }';
			$inline_css .= '.smart-toc .toc-item > a.active, .smart-toc .toc-item > .toc-item-inner > a.active { background: ' . esc_attr( $active_bg_color ) . ' !important; color: ' . esc_attr( $active_text_color ) . ' !important; border-left-color: ' . esc_attr( $active_border_color ) . ' !important; box-shadow: inset 0 0 0 1px ' . esc_attr( $active_outline_color ) . '; }';
			$inline_css .= '.smart-toc-header { border-left-color: ' . esc_attr( $accent_color ) . '; }';
			$inline_css .= '.smart-toc-reading-progress { background: ' . esc_attr( $accent_color ) . '; }';
			$inline_css .= '.smart-toc-floating-btn { background: ' . esc_attr( $accent_color ) . ' !important; }';
			$inline_css .= '.smart-toc-floating-btn:hover { background: ' . esc_attr( $hover_color ) . ' !important; }';
			$inline_css .= '.smart-toc-back-to-top { --btt-bg-color: ' . esc_attr( $accent_color ) . '; --btt-bg-hover-color: ' . esc_attr( $hover_color ) . '; }';
			$inline_css .= '.smart-toc.icon-style-plus_minus .smart-toc-toggle:hover .toggle-icon::before, .smart-toc.icon-style-plus_minus .smart-toc-toggle:hover .toggle-icon::after { background-color: ' . esc_attr( $accent_color ) . ' !important; }';
			wp_add_inline_style( 'aniksmta-toc', $inline_css );
		}

		wp_enqueue_script(
			'aniksmta-toc-js',
			ANIKSMTA_URL . 'assets/js/toc.js',
			array(),
			ANIKSMTA_VERSION,
			true
		);

		wp_localize_script(
			'aniksmta-toc-js',
			'aniksmtaSettings',
			array(
				'smoothScroll'               => $this->settings->get( 'smooth_scroll' ),
				'highlightActive'            => $this->settings->get( 'highlight_active' ),
				'scrollOffset'               => $this->settings->get( 'scroll_offset', 80 ),
				'copyLink'                   => $this->settings->get( 'copy_link', true ),
				'readingProgress'            => $this->settings->get( 'reading_progress', true ),
				'dynamicContent'             => $this->settings->get( 'dynamic_content', true ),
				'lazyLoad'                   => $this->settings->get( 'lazy_load_toc', true ),
				'mobileModal'                => $this->settings->get( 'mobile_toc_modal', false ),
				'floatingDesktop'            => $this->settings->get( 'floating_desktop', true ),
				'floatingTocPosition'        => $this->settings->get( 'floating_toc_position', 'right' ),
				'floatingTocStyle'           => $this->settings->get( 'floating_toc_style', 'icon_text' ),
				'floatingTocTheme'           => $this->settings->get( 'floating_toc_theme', 'dark' ),
				'floatingTocPanelWidth'      => $this->settings->get( 'floating_toc_panel_width', 320 ),
				'floatingTocAutoClose'       => $this->settings->get( 'floating_toc_auto_close', true ),
				'floatingTocShowProgress'    => $this->settings->get( 'floating_toc_show_progress', true ),
				'floatingTocDefaultExpanded' => $this->settings->get( 'floating_toc_default_expanded', true ),
				'stickyPosition'             => $this->settings->get( 'sticky_position', 'inline' ),
				'stickyWidth'                => $this->settings->get( 'sticky_width', 280 ),
				'stickyOffsetTop'            => $this->settings->get( 'sticky_offset', 20 ),
				'collapsibleSections'        => $this->settings->get( 'collapsible_sections', true ),
				'sectionsCollapsed'          => $this->settings->get( 'sections_collapsed', false ),
				'backToTop'                  => $this->settings->get( 'back_to_top', false ),
				'backToTopIcon'              => $this->settings->get( 'back_to_top_icon', 'arrow' ),
				'backToTopStyle'             => $this->settings->get( 'back_to_top_style', 'circle' ),
				'backToTopBgColor'           => $this->settings->get( 'back_to_top_bg_color', '' ),
				'backToTopIconColor'         => $this->settings->get( 'back_to_top_icon_color', '#ffffff' ),
				'backToTopShowDesktop'       => $this->settings->get( 'back_to_top_show_desktop', true ),
				'backToTopShowTablet'        => $this->settings->get( 'back_to_top_show_tablet', true ),
				'backToTopShowMobile'        => $this->settings->get( 'back_to_top_show_mobile', true ),
				'autoDarkMode'               => $this->settings->get( 'auto_dark_mode', false ),
				'copyLabel'                  => __( 'Copy', 'anik-smart-table-of-contents' ),
				'copySuccessLabel'           => __( 'Copied', 'anik-smart-table-of-contents' ),
				'copyErrorLabel'             => __( 'Error', 'anik-smart-table-of-contents' ),
				'mobileOpenLabel'            => __( 'Contents', 'anik-smart-table-of-contents' ),
				'mobileCloseLabel'           => __( 'Close', 'anik-smart-table-of-contents' ),
				'desktopOpenLabel'           => __( 'Table of Contents', 'anik-smart-table-of-contents' ),
			)
		);
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

		// Expand 3-char hex to 6-char.
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );

		$r = max( 0, min( 255, $r + $steps ) );
		$g = max( 0, min( 255, $g + $steps ) );
		$b = max( 0, min( 255, $b + $steps ) );

		return sprintf( '#%02x%02x%02x', $r, $g, $b );
	}

	/**
	 * Determine if a hex color is considered light.
	 *
	 * @param string $hex Hex color.
	 * @return bool
	 */
	private function is_light_color( $hex ) {
		$rgb = $this->hex_to_rgb( $hex );
		if ( null === $rgb ) {
			return false;
		}

		$luminance = ( ( 0.299 * $rgb['r'] ) + ( 0.587 * $rgb['g'] ) + ( 0.114 * $rgb['b'] ) ) / 255;
		return $luminance >= 0.78;
	}

	/**
	 * Get readable text color for a given background color.
	 *
	 * @param string $background_hex Hex background color.
	 * @return string
	 */
	private function get_contrast_text_color( $background_hex ) {
		return $this->is_light_color( $background_hex ) ? '#1f2937' : '#ffffff';
	}

	/**
	 * Convert hex color to RGB array.
	 *
	 * @param string $hex Hex color.
	 * @return array|null
	 */
	private function hex_to_rgb( $hex ) {
		$hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return null;
		}

		return array(
			'r' => hexdec( substr( $hex, 0, 2 ) ),
			'g' => hexdec( substr( $hex, 2, 2 ) ),
			'b' => hexdec( substr( $hex, 4, 2 ) ),
		);
	}

	/**
	 * Main render function
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function render( $content ) {
		if ( ! in_the_loop() || ! is_main_query() ) {
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

		$this->toc_rendered  = true;
		$this->generated_toc = $toc_data['toc'];

		// Build schema JSON-LD if enabled.
		$schema_html = '';
		if ( $this->settings->get( 'schema_enabled' ) ) {
			$schema_html = $this->build_schema( $toc_data['items'] );
		}

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

		return $content . $schema_html;
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
		$min_headings   = $this->settings->get( 'min_headings' );
		$exclude_class  = $this->settings->get( 'exclude_class' );

		// Per-post heading level override.
		if ( is_singular() ) {
			$post_id       = get_the_ID();
			$custom_levels = get_post_meta( $post_id, '_aniksmta_heading_levels', true );
			if ( ! empty( $custom_levels ) && is_array( $custom_levels ) ) {
				$heading_levels = $custom_levels;
			}
		}

		// Bail early if no heading levels are configured.
		if ( empty( $heading_levels ) ) {
			return array(
				'toc'     => '',
				'content' => $content,
			);
		}

		// Build regex pattern for heading levels
		$levels  = implode( '', $heading_levels );
		$pattern = '/<h([' . $levels . '])([^>]*)>(.*?)<\/h\1>/si';

		preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER );

		// Get exclude headings by text.
		$exclude_headings_text = $this->settings->get( 'exclude_headings', '' );
		$exclude_texts         = array();
		if ( ! empty( $exclude_headings_text ) ) {
			$exclude_texts = array_map( 'trim', explode( ',', $exclude_headings_text ) );
			$exclude_texts = array_filter( $exclude_texts );
		}

		// Filter out excluded headings
		$headings = array();
		foreach ( $matches as $match ) {
			if ( ! empty( $exclude_class ) && false !== strpos( $match[2], $exclude_class ) ) {
				continue;
			}

			// Exclude by heading text.
			$clean_heading_text = wp_strip_all_tags( $match[3] );
			$is_excluded        = false;
			foreach ( $exclude_texts as $exclude_text ) {
				if ( false !== stripos( $clean_heading_text, $exclude_text ) ) {
					$is_excluded = true;
					break;
				}
			}
			if ( $is_excluded ) {
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
		$toc_items         = array();
		$processed_content = $content;
		$used_ids          = array();

		foreach ( $headings as $index => $heading ) {
			$level      = $heading[1];
			$attrs      = $heading[2];
			$text       = $heading[3];
			$clean_text = wp_strip_all_tags( $text );

			// Check if heading already has an ID.
			if ( preg_match( '/id=["\']([^"\']+)["\']/', $attrs, $id_match ) ) {
				$id         = $id_match[1];
				$used_ids[] = $id;
			} else {
				// Generate unique ID.
				$id         = $this->generate_heading_id( $clean_text, $used_ids );
				$used_ids[] = $id;

				// Add ID to heading; use positional replace to avoid duplicate heading corruption.
				$new_heading = sprintf(
					'<h%s id="%s"%s>%s</h%s>',
					$level,
					esc_attr( $id ),
					$attrs,
					$text,
					$level
				);
				$pos         = strpos( $processed_content, $heading[0] );
				if ( false !== $pos ) {
					$processed_content = substr_replace( $processed_content, $new_heading, $pos, strlen( $heading[0] ) );
				}
			}

			$toc_items[] = array(
				'level' => (int) $level,
				'text'  => $clean_text,
				'id'    => $id,
			);
		}

		// Calculate reading time
		if ( $this->settings->get( 'reading_time', true ) ) {
			$word_count                     = count( preg_split( '/\s+/', wp_strip_all_tags( $content ) ) );
			$overrides['reading_time_mins'] = max( 1, (int) ceil( $word_count / 200 ) );
		}

		$toc_html = $this->build_toc_html( $toc_items, $overrides );

		return array(
			'toc'     => $toc_html,
			'content' => $processed_content,
			'items'   => $toc_items,
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
		$counter     = 1;
		while ( in_array( $id, $used_ids, true ) ) {
			$id = $original_id . '-' . $counter;
			++$counter;
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

		$title     = isset( $overrides['title'] ) ? $overrides['title'] : $this->settings->get_title();
		$collapsed = isset( $overrides['collapsed'] ) ? $overrides['collapsed'] : $this->settings->get( 'default_collapsed' );

		$collapsed_class = $collapsed ? ' collapsed' : '';
		$aria_expanded   = $collapsed ? 'false' : 'true';
		$sticky_class    = '';
		$sticky_style    = '';
		$sticky_attrs    = '';
		$mobile_class    = '';
		$icon_style      = $this->settings->get( 'toggle_icon_style', 'chevron' );
		$icon_class      = ' icon-style-' . sanitize_html_class( $icon_style );

		if ( $this->settings->get( 'sticky_toc', false ) ) {
			$sticky_position = $this->settings->get( 'sticky_position', 'inline' );
			if ( ! in_array( $sticky_position, array( 'inline', 'left', 'right' ), true ) ) {
				$sticky_position = 'inline';
			}

			$sticky_class = ' smart-toc-sticky smart-toc-sticky-' . sanitize_html_class( $sticky_position );
			$sticky_top   = absint( $this->settings->get( 'sticky_offset', 20 ) );
			$sticky_width = absint( $this->settings->get( 'sticky_width', 280 ) );

			if ( 'inline' === $sticky_position ) {
				$sticky_style = ' style="--aniksmta-sticky-top: ' . esc_attr( $sticky_top ) . 'px;"';
			}

			$sticky_attrs  = ' data-sticky-position="' . esc_attr( $sticky_position ) . '"';
			$sticky_attrs .= ' data-sticky-width="' . esc_attr( $sticky_width ) . '"';
			$sticky_attrs .= ' data-sticky-offset="' . esc_attr( $sticky_top ) . '"';
		}

		if ( $this->settings->get( 'mobile_toc_modal', false ) ) {
			$mobile_class = ' smart-toc-mobile-modal';
		}

		// TOC theme class.
		$toc_theme   = $this->settings->get( 'toc_theme', 'dark' );
		$theme_class = ' toc-theme-' . sanitize_html_class( $toc_theme );

		$reading_time_html = '';
		if ( $this->settings->get( 'reading_time', true ) && isset( $overrides['reading_time_mins'] ) ) {
			$mins = $overrides['reading_time_mins'];
			/* translators: %d: Estimated reading time in minutes. */
			$time_text         = sprintf( _n( '%d min read', '%d mins read', $mins, 'anik-smart-table-of-contents' ), $mins );
			$reading_time_html = '<span class="smart-toc-reading-time">' . esc_html( $time_text ) . '</span>';
		}

		$html  = '<nav class="smart-toc' . esc_attr( $collapsed_class . $theme_class . $sticky_class . $mobile_class . $icon_class ) . '"' . $sticky_style . $sticky_attrs . ' aria-label="' . esc_attr__( 'Table of Contents', 'anik-smart-table-of-contents' ) . '">';
		$html .= '<div class="smart-toc-header">';
		$html .= '<div class="smart-toc-title-wrapper">';
		$html .= '<span class="smart-toc-title">' . esc_html( $title ) . '</span>';
		if ( $reading_time_html ) {
			$html .= $reading_time_html;
		}
		$html .= '</div>';
		$html .= '<button class="smart-toc-toggle" aria-expanded="' . esc_attr( $aria_expanded ) . '" aria-label="' . esc_attr__( 'Toggle Table of Contents', 'anik-smart-table-of-contents' ) . '">';
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

		$show_numbers   = (bool) $this->settings->get( 'show_numbers', false );
		$counter_format = $this->settings->get( 'counter_format', 'decimal' );

		// If counter format is 'none', disable numbers regardless.
		if ( 'none' === $counter_format ) {
			$show_numbers = false;
		}

		$html                  = '<ul class="smart-toc-list">';
		$flat_counter          = 1;
		$hierarchical_counters = array();
		$copy_link             = (bool) $this->settings->get( 'copy_link', true );

		// Determine the highest heading level present to use as the base
		$base_level = 6;
		foreach ( $items as $item ) {
			if ( $item['level'] < $base_level ) {
				$base_level = $item['level'];
			}
		}

		foreach ( $items as $item ) {
			$level        = $item['level'];
			$indent_class = 'toc-level-' . $level;
			$number_html  = '';

			if ( $show_numbers ) {
				if ( 'hierarchical' === $counter_format ) {
					// Reset deeper levels
					foreach ( $hierarchical_counters as $l => $v ) {
						if ( $l > $level ) {
							unset( $hierarchical_counters[ $l ] );
						}
					}
					$hierarchical_counters[ $level ] = isset( $hierarchical_counters[ $level ] ) ? $hierarchical_counters[ $level ] + 1 : 1;

					$parts = array();
					for ( $i = $base_level; $i <= $level; $i++ ) {
						$parts[] = isset( $hierarchical_counters[ $i ] ) ? $hierarchical_counters[ $i ] : 1;
					}
					$number_html = '<span class="toc-number">' . esc_html( implode( '.', $parts ) ) . '</span> ';
				} else {
					$number_html = '<span class="toc-number">' . esc_html( $this->format_counter( $flat_counter, $counter_format ) ) . '.</span> ';
				}
			}

			$html .= '<li class="toc-item ' . esc_attr( $indent_class ) . '">';
			$html .= '<a href="#' . esc_attr( $item['id'] ) . '">' . $number_html . esc_html( $item['text'] ) . '</a>';
			$html .= '</li>';
			++$flat_counter;
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	 * Format counter number based on format type
	 *
	 * @param int    $number Counter number.
	 * @param string $format Format type (decimal, roman).
	 * @return string
	 */
	private function format_counter( $number, $format ) {
		if ( 'roman' === $format ) {
			return $this->to_roman( $number );
		}

		return (string) $number;
	}

	/**
	 * Convert integer to Roman numeral
	 *
	 * @param int $number Number to convert.
	 * @return string
	 */
	private function to_roman( $number ) {
		$map = array(
			'M'  => 1000,
			'CM' => 900,
			'D'  => 500,
			'CD' => 400,
			'C'  => 100,
			'XC' => 90,
			'L'  => 50,
			'XL' => 40,
			'X'  => 10,
			'IX' => 9,
			'V'  => 5,
			'IV' => 4,
			'I'  => 1,
		);

		$result = '';
		foreach ( $map as $roman => $value ) {
			while ( $number >= $value ) {
				$result .= $roman;
				$number -= $value;
			}
		}

		return $result;
	}

	/**
	 * Build SiteNavigationElement JSON-LD schema
	 *
	 * @param array $items TOC items.
	 * @return string
	 */
	private function build_schema( $items ) {
		if ( empty( $items ) ) {
			return '';
		}

		$permalink = get_permalink();
		if ( ! $permalink ) {
			return '';
		}

		$elements = array();
		foreach ( $items as $item ) {
			$elements[] = array(
				'@type' => 'SiteNavigationElement',
				'name'  => $item['text'],
				'url'   => $permalink . '#' . $item['id'],
			);
		}

		$schema = array(
			'@context' => 'https://schema.org',
			'@graph'   => $elements,
		);

		return '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
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
