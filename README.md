# Smart Table of Contents

A lightweight, SEO-friendly WordPress Table of Contents plugin that auto-generates a clickable TOC from post and page headings.

- Plugin slug: `anik-smart-table-of-contents`
- Current version: `1.2.0`
- Requires WordPress: `5.0+`
- Requires PHP: `7.4+`
- Tested up to: `6.9`

## Overview

Smart Table of Contents helps readers navigate long content with structured anchor links, smooth scrolling, collapsible sections, and active heading highlighting. It is designed to be fast, simple, and compatible with most themes and page builders.

## Free Features

- Automatic TOC generation from H2-H6
- Smooth scrolling
- Collapsible TOC
- Active section highlight on scroll
- Shortcode support
- Heading level control
- Counter format options (decimal, roman, hierarchical, none)
- Collapsible nested sections
- Theme presets and accent color
- Toggle icon styles (chevron or plus/minus)
- Optional auto dark mode
- JSON-LD SiteNavigation schema support
- Per-post TOC control and per-post heading override
- Exclude headings by text or CSS class (`no-toc`)
- Page exclusions (home, archive, search, 404)
- Placement options (before content, after first paragraph, manual)
- Basic sticky TOC
- Basic floating desktop TOC
- Basic mobile TOC modal
- Copy anchor links
- Basic reading progress bar
- Basic reading time
- Basic back-to-top button
- Dynamic content refresh
- Lazy-load TOC initialization
- Basic Gutenberg block
- Basic sidebar widget
- Dashboard widget

## Installation

1. Upload the `anik-smart-table-of-contents` folder to `/wp-content/plugins/`.
2. Activate the plugin from the WordPress Plugins page.
3. Go to `Settings -> Smart TOC` and configure options.

## Usage

### Automatic Placement

Use plugin settings to show TOC automatically in supported post types.

### Shortcode

Use the shortcode where you want TOC to appear:

```text
[aniksmta_toc]
```

Shortcode examples:

```text
[aniksmta_toc title="In This Article"]
[aniksmta_toc collapsed="true"]
```

For shortcode-only mode, set TOC position to manual in plugin settings.

### Exclude Specific Headings

Add class `no-toc` to a heading:

```html
<h2 class="no-toc">This heading will be excluded</h2>
```

## Compatibility

- Works with Gutenberg and common page builders
- Compatible with common caching plugins
- RTL language support
- Translation ready (`languages/anik-smart-table-of-contents.pot`)

## Pro Version

Upgrade options and premium features:

- Product page: https://smallseoengine.com/plugins/smart-table-of-contents/

## Support

- Documentation: https://smallseoengine.com/plugins/anik-smart-table-of-contents/docs
- WordPress support forum: https://wordpress.org/support/plugin/anik-smart-table-of-contents/
- Contact: https://smallseoengine.com/contact/
- Bug reports: https://github.com/wordpress-plugins-project/smart-table-of-content-free-plugin/issues

## Changelog

### 1.2.0

- Added Gutenberg block support
- Added sidebar widget support
- Added basic sticky TOC
- Added mobile TOC modal
- Added TOC item copy link button
- Added basic reading progress bar

## License

GPLv2 or later

- License URI: https://www.gnu.org/licenses/gpl-2.0.html
