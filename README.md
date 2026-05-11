# Smart Table of Contents - WordPress Table of Contents Plugin

Smart Table of Contents is a lightweight WordPress Table of Contents plugin that automatically generates a clickable TOC from post and page headings. It improves content navigation, internal anchor structure, and long-form reading experience.

- Plugin slug: `anik-smart-table-of-contents`
- Current version: `1.2.0`
- Requires WordPress: `5.0+`
- Requires PHP: `7.4+`
- Tested up to: `6.9`

## Quick Links

- WordPress.org plugin page: https://wordpress.org/plugins/anik-smart-table-of-contents/
- Documentation: https://smallseoengine.com/plugins/anik-smart-table-of-contents/docs
- Support forum: https://wordpress.org/support/plugin/anik-smart-table-of-contents/
- Issue tracker: https://github.com/wordpress-plugins-project/smart-table-of-content-free-plugin/issues
- Pro features: https://smallseoengine.com/plugins/smart-table-of-contents/

## Why Use This TOC Plugin

This plugin is built for websites that publish long articles, tutorials, documentation, list posts, and evergreen SEO content. It helps visitors jump to sections quickly and helps structure headings with crawlable anchor links.

Common search intents this plugin covers:

- WordPress table of contents plugin
- TOC plugin for long posts
- Auto generate heading links in WordPress
- Collapsible table of contents for blog posts
- Gutenberg table of contents block

## Core Free Features

- Automatic TOC generation from H2-H6 headings
- Smooth scrolling and active section highlight
- Collapsible TOC and collapsible nested sections
- Shortcode support: `[aniksmta_toc]`
- Counter formats: decimal, roman, hierarchical, none
- Theme presets, accent color, toggle icon styles
- JSON-LD SiteNavigation schema support
- Per-post enable/disable and heading-level override
- Exclude headings by text or CSS class (`no-toc`)
- Display condition controls (home, archive, search, 404)
- Placement modes: before content, after first paragraph, manual
- Basic sticky TOC, floating desktop TOC, and mobile TOC modal
- Copy anchor links, basic reading progress, reading time, back-to-top
- Dynamic content refresh and lazy-load TOC initialization
- Basic Gutenberg block, sidebar widget, and dashboard widget

## Installation

1. Upload `anik-smart-table-of-contents` to `/wp-content/plugins/`.
2. Activate it from WordPress Plugins.
3. Go to `Settings -> Smart TOC`.
4. Choose auto placement or shortcode-only mode.

## Shortcode Usage

```text
[aniksmta_toc]
[aniksmta_toc title="In This Article"]
[aniksmta_toc collapsed="true"]
```

For manual-only placement, set TOC position to manual in plugin settings.

## Frequently Asked Questions

### Does this plugin help SEO?

It helps with heading-based page navigation and cleaner internal anchor structure. It also supports SiteNavigation JSON-LD output.

### Can I use this with Elementor, Gutenberg, or other builders?

Yes. It works with Gutenberg and common WordPress page builders.

### Can I hide the TOC on specific posts?

Yes. You can disable TOC per post and control heading levels per post.

### How do I exclude a heading from TOC?

Use CSS class `no-toc` on the heading:

```html
<h2 class="no-toc">This heading is excluded</h2>
```

## Compatibility

- Gutenberg and common page builders
- Popular caching plugins
- RTL languages
- Translation ready (`languages/anik-smart-table-of-contents.pot`)

## Changelog (Latest)

### 1.2.0

- Added Gutenberg block support
- Added sidebar widget support
- Added basic sticky TOC
- Added mobile TOC modal
- Added TOC item copy link button
- Added basic reading progress bar

## License

GPLv2 or later  
https://www.gnu.org/licenses/gpl-2.0.html
