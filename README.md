# Small SEO Engine Smart TOC

[![WordPress Plugin Version](https://img.shields.io/badge/version-1.0.7-blue)](https://wordpress.org/plugins/small-seo-engine-smart-toc/)
[![WordPress Tested](https://img.shields.io/badge/WordPress-6.9%20tested-brightgreen)](https://wordpress.org/plugins/small-seo-engine-smart-toc/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPLv2-orange)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Buy Me a Coffee](https://img.shields.io/badge/Donate-Buy%20Me%20a%20Coffee-yellow?logo=buymeacoffee)](https://buymeacoffee.com/anikchowdhury)

> A lightweight, SEO-friendly Table of Contents plugin for WordPress that automatically generates navigation from your headings with smooth scroll and collapsible features.

![Smart TOC Preview](https://via.placeholder.com/800x400?text=Smart+Table+of+Contents+Preview)

## 🚀 Features

- **Automatic TOC Generation** - Automatically scans your content and creates a table of contents from headings
- **Smooth Scrolling** - Elegant smooth scroll animation to sections when clicking TOC links
- **Collapsible TOC** - Allow visitors to expand/collapse the table of contents
- **Active Heading Highlight** - Highlights the current section as users scroll through content
- **Shortcode Support** - Use `[smart_toc]` to place TOC anywhere in your content
- **Customizable Heading Levels** - Choose which heading levels (H2-H6) to include
- **Show Numbers** - Optional sequential numbering for TOC items (1, 2, 3...)
- **Theme Color** - Match your site's design with custom theme color
- **SEO Friendly** - Clean HTML markup optimized for search engines
- **Lightweight** - Minimal footprint, fast loading with no dependencies
- **Per-Post Control** - Enable/disable TOC for individual posts
- **Translation Ready** - Fully translatable with i18n support

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher

## 💾 Installation

### From WordPress Admin

1. Go to **Plugins → Add New**
2. Search for "Small SEO Engine Smart TOC"
3. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the plugin zip file
2. Upload to `/wp-content/plugins/` directory
3. Extract the zip file
4. Activate the plugin through the **Plugins** menu in WordPress

### From GitHub

```bash
cd wp-content/plugins/
git clone https://github.com/wordpress-plugins-project/small-seo-engine-smart-toc.git smart-toc
```

## ⚙️ Configuration

After activation, go to **Settings → Smart TOC** to configure:

| Setting | Description |
| ------- | ----------- |
| Enable TOC | Globally enable/disable the table of contents |
| Post Types | Select which post types should display TOC |
| Minimum Headings | Minimum number of headings required to show TOC |
| Heading Levels | Choose which heading levels (H2-H6) to include |
| Default Collapsed | Start TOC in collapsed state |
| Show Numbers | Display sequential numbers before TOC items |
| Position | Before content, After first paragraph, or Manual |
| Smooth Scroll | Enable smooth scrolling animation |
| Highlight Active | Highlight current section in TOC |
| Theme Color | Customize the accent color |
| Scroll Offset | Offset for fixed headers (in pixels) |

## 📝 Usage

### Automatic Display

Once configured, the TOC will automatically appear on your posts and pages based on your settings.

### Shortcode

Use the shortcode for manual placement:

```
[smart_toc]
```

**With custom title:**
```
[smart_toc title="In This Article"]
```

**Collapsed by default:**
```
[smart_toc collapsed="true"]
```

### Excluding Headings

Add the `no-toc` class to any heading you want to exclude:

```html
<h2 class="no-toc">This heading won't appear in TOC</h2>
```

## 📁 File Structure

```text
small-seo-engine-smart-toc/
├── small-seo-engine-smart-toc.php       # Main plugin file
├── uninstall.php                # Cleanup on uninstall
├── index.php                    # Security index
├── readme.txt                   # WordPress.org readme
├── assets/
│   ├── css/
│   │   ├── admin.css            # Admin styles
│   │   └── toc.css              # Frontend TOC styles
│   └── js/
│       └── toc.js               # Frontend TOC functionality
├── includes/
│   ├── class-core.php           # Core plugin class
│   ├── class-admin.php          # Admin functionality & Documentation
│   ├── class-settings.php       # Settings handler
│   ├── class-render.php         # TOC rendering
│   └── class-shortcode.php      # Shortcode handler
└── languages/
    └── small-seo-engine-smart-toc.pot      # Translation template
```

## 🔌 Compatibility

Smart TOC works seamlessly with:

- **Page Builders**: Elementor, Beaver Builder, Divi, WPBakery
- **Themes**: Works with any properly coded WordPress theme
- **Caching Plugins**: WP Super Cache, W3 Total Cache, WP Rocket, LiteSpeed Cache
- **SEO Plugins**: Yoast SEO, Rank Math, All in One SEO

## 🚀 Looking for More Features?

**[Smart TOC Pro](https://smallseoengine.com/plugins/smart-table-of-content/)** extends this plugin with advanced features:

- 📌 Sticky/Floating TOC
- 📊 Reading Progress Bar  
- ⏱️ Estimated Reading Time
- ⬆️ Back to Top Button
- ⌨️ Keyboard Navigation
- 🎨 Multiple Theme Presets
- � Custom CSS Support
- 📱 Mobile-Specific Options
- 🔢 Hierarchical Heading Numbers (1.1, 1.2, 2.1...)
- 🧱 Gutenberg Block & Sidebar Widget
- 🌟 Priority Support

[Learn More →](https://smallseoengine.com/plugins/smart-table-of-content/)

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) file for details.

## 📞 Support

- **Documentation**: [Read Documentation](https://github.com/wordpress-plugins-project/small-seo-engine-smart-toc#readme)
- **WordPress.org**: [Support Forum](https://wordpress.org/support/plugin/small-seo-engine-smart-toc/)
- **Issues**: [GitHub Issues](https://github.com/wordpress-plugins-project/small-seo-engine-smart-toc/issues)
- **Website**: [Small SEO Engine](https://smallseoengine.com)
- **Buy Me a Coffee**: [Support Development](https://buymeacoffee.com/anikchowdhury)

## 📝 Changelog

### 1.0.7
- Renamed plugin from "Smart Table of Contents" to "Small SEO Engine Smart TOC" for distinctive branding
- Updated text domain from `smart-table-of-contents` to `small-seo-engine-smart-toc`
- Updated all plugin references, URLs, and documentation to reflect new name
- Renamed main plugin file to `small-seo-engine-smart-toc.php`
- Updated translation template (.pot file) with new text domain

### 1.0.5
- Fixed text domain mismatch for proper internationalization
- Renamed admin menu to "Smart TOC Lite" for clear branding
- Added PHPCS with WordPress Coding Standards configuration
- Applied WordPress coding standards formatting throughout
- Fixed all PHPCS errors and warnings
- Updated minimum PHP requirement to 7.4
- Code quality improvements and cleanup

### 1.0.4
- Added Documentation tab in admin settings panel with comprehensive user guide
- Includes Quick Start guide, Settings Reference, Shortcode Usage, Troubleshooting, and FAQ
- Improved admin UI with tabbed navigation
- Renamed main plugin file to match WordPress.org slug

### 1.0.3
- Renamed the translation text domain to `small-seo-engine-smart-toc` to align with the plugin slug
- Updated POT file and text-domain references throughout the admin UI

### 1.0.2
- Added missing `ABSPATH` checks and normalized line endings for Plugin Check compliance
- Bumped internal version constants for asset cache busting

### 1.0.1
- Improved scrolling behavior for last TOC items
- Added show-number option to frontend output and settings defaults
- Updated documentation and admin asset slugs

### 1.0.0

- Initial release
- Automatic TOC generation
- Smooth scroll navigation
- Collapsible TOC
- Active heading highlight
- Shortcode support
- Theme color customization
- Show numbers option
- Per-post enable/disable
- Multi-language ready

---

**Made with ❤️ by [Small SEO Engine](https://smallseoengine.com) for the WordPress community**

If you find this plugin helpful, please consider:
- ⭐ Giving it a star on GitHub
- 📝 Leaving a review on [WordPress.org](https://wordpress.org/plugins/small-seo-engine-smart-toc/)
- ☕ [Buy me a coffee](https://buymeacoffee.com/anikchowdhury)
