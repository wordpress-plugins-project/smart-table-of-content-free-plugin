=== Smart Table of Contents ===
Contributors: anikchowdhury
Donate link: https://anikchowdhury.net
Tags: table of contents, toc, seo, navigation, headings
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.0.3
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight, SEO-friendly Table of Contents plugin that automatically generates TOC from your headings with smooth scroll and collapsible features.

== Description ==

**Smart Table of Contents** automatically creates a beautiful, SEO-friendly table of contents from your post and page headings. It helps visitors navigate long-form content easily while improving your site's SEO with structured content.

https://www.youtube.com/watch?v=YOUR_VIDEO_ID

**🎯 Perfect for:**
* Bloggers with long-form content
* Documentation sites
* Tutorial websites
* News and magazine sites
* Educational content
* Any site with lengthy articles

= ✨ Key Features =

* **Automatic TOC Generation** - Automatically scans your content and creates a table of contents from headings
* **Smooth Scrolling** - Elegant smooth scroll animation when clicking TOC links
* **Collapsible TOC** - Allow visitors to expand/collapse the table of contents
* **Active Heading Highlight** - Highlights the current section as users scroll through content
* **Shortcode Support** - Use `[smart_toc]` to place TOC anywhere in your content
* **Customizable Headings** - Choose which heading levels to include (H2-H6)
* **Show Numbers** - Optional numbering for TOC items (1, 2, 3...)
* **Theme Color** - Match your site's design with custom accent color
* **SEO Friendly** - Clean HTML markup with proper anchor links for search engines
* **Lightweight** - Under 15KB, no jQuery dependency for frontend
* **Translation Ready** - Fully translatable with POT file included
* **Per-Post Control** - Disable TOC for specific posts/pages

= How It Works =

1. Install and activate the plugin
2. Configure settings under Settings → Smart TOC
3. The TOC will automatically appear on your posts and pages
4. Or use `[smart_toc]` shortcode for manual placement

= Shortcode Usage =

**Basic usage:**
`[smart_toc]`

**With custom title:**
`[smart_toc title="In This Article"]`

**Collapsed by default:**
`[smart_toc collapsed="true"]`

= Pro Version =

Need more features? Check out **[Smart TOC Pro](https://anikchowdhury.net/smart-toc-pro/)** with:

* Sticky/Floating TOC
* Reading progress bar
* Estimated reading time
* Back to top button
* Keyboard navigation
* Multiple theme presets
* Custom CSS support
* Mobile-specific options
* Collapsible sections
* Heading numbers
* Gutenberg block
* Sidebar widget
* Priority support

== Installation ==

1. Upload the `smart-toc` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → Smart TOC to configure the plugin
4. That's it! TOC will automatically appear on your posts and pages

== Frequently Asked Questions ==

= How do I add TOC to a specific location? =

Use the shortcode `[smart_toc]` in your post or page content where you want the TOC to appear. Set the position to "Manual (Shortcode Only)" in settings.

= Can I exclude a specific post from having TOC? =

Yes! Edit the post and look for the "Smart TOC" meta box in the sidebar. Check the box to disable TOC for that specific post or page.

= How do I change the TOC position? =

Go to Settings → Smart TOC → Display Settings and choose from:
* **Before Content** - TOC appears at the top of your content
* **After First Paragraph** - TOC appears after the first paragraph
* **Manual (Shortcode only)** - Use `[smart_toc]` shortcode for custom placement

= Can I exclude certain headings from TOC? =

Yes! Add the CSS class `no-toc` to any heading you want to exclude:
`<h2 class="no-toc">This heading won't appear in TOC</h2>`

= How do I change the TOC title? =

Go to Settings → Smart TOC → Display Settings and change the "TOC Title" field. Or use the shortcode attribute: `[smart_toc title="In This Article"]`

= Does it work with page builders? =

Yes, Smart TOC works with most page builders including:
* Elementor
* Beaver Builder
* Divi Builder
* WPBakery
* Gutenberg

= Is it compatible with caching plugins? =

Yes, Smart TOC is fully compatible with popular caching plugins:
* WP Super Cache
* W3 Total Cache
* WP Rocket
* LiteSpeed Cache
* Autoptimize

= Why isn't the TOC showing on my posts? =

Check these common issues:
1. Make sure the plugin is enabled in Settings → Smart TOC
2. Verify the post type is selected in settings
3. Check if you have the minimum number of headings required
4. Make sure TOC isn't disabled for that specific post

= Does it affect page speed? =

No! Smart TOC is extremely lightweight (under 15KB) and doesn't use jQuery on the frontend. It has minimal impact on your page load time.

= Can I style the TOC to match my theme? =

Yes! You can:
1. Change the theme color in settings
2. Add custom CSS to your theme
3. The TOC uses clean HTML with CSS classes for easy styling

= Does it support RTL languages? =

Yes, Smart TOC fully supports RTL (Right-to-Left) languages like Arabic, Hebrew, and Persian.

= How do I display numbers before TOC items? =

Go to Settings → Smart TOC → Display Settings and check "Show Numbers" option.

== Screenshots ==

1. Table of Contents displayed on a post
2. Admin settings page - General settings
3. Admin settings page - Display settings
4. Collapsed TOC view
5. Per-post TOC settings

== Changelog ==

= 1.0.3 =
* Renamed the translation text domain to `smart-table-of-contents` to align with the plugin slug and silence Plugin Checker warnings
* Updated the included POT file and text-domain references throughout the admin UI for consistent localization behavior

= 1.0.2 =
* Added missing `ABSPATH` checks and normalized line endings for Plugin Check compliance
* Bumped internal version constants to ensure cache busting for assets
* Minor documentation updates for WordPress.org submission

= 1.0.1 =
* Improved scrolling behavior for last TOC items and removed inner scrollbar
* Added show-number option to frontend output and settings defaults
* Updated documentation links and admin assets to new slug

= 1.0.0 =
* Initial release
* Automatic TOC generation
* Smooth scroll navigation
* Collapsible TOC
* Active heading highlight
* Shortcode support
* Theme color customization
* Per-post enable/disable
* Show numbers option
* Multi-language ready

== Upgrade Notice ==

= 1.0.3 =
Required if you plan to localize the plugin or submit to WordPress.org—text domain now matches the official slug.

= 1.0.2 =
Recommended update for additional security hardening and WordPress.org review compliance.

= 1.0.1 =
Includes improved scrolling, numbering, and admin assets—update to keep frontend behavior consistent.

= 1.0.0 =
Initial release of Smart Table of Contents.
