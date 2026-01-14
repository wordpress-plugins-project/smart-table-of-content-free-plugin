# Smart Table of Contents

[![WordPress Plugin Version](https://img.shields.io/badge/version-1.0.0-blue)](https://wordpress.org/plugins/smart-toc/)
[![WordPress Tested](https://img.shields.io/badge/WordPress-6.7%20tested-brightgreen)](https://wordpress.org/plugins/smart-toc/)
[![PHP Version](https://img.shields.io/badge/PHP-7.2%2B-purple)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPLv2-orange)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Downloads](https://img.shields.io/badge/downloads-1k%2B-green)](https://wordpress.org/plugins/smart-toc/)

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
2. Search for "Smart Table of Contents"
3. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the plugin zip file
2. Upload to `/wp-content/plugins/` directory
3. Extract the zip file
4. Activate the plugin through the **Plugins** menu in WordPress

### From GitHub

```bash
cd wp-content/plugins/
git clone https://github.com/wordpress-plugins-project/smart-table-of-content-free-plugin.git smart-toc
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
smart-toc/
├── smart-toc.php          # Main plugin file
├── uninstall.php          # Cleanup on uninstall
├── index.php              # Security index
├── readme.txt             # WordPress.org readme
├── README.md              # GitHub readme
├── assets/
│   ├── css/
│   │   ├── admin.css      # Admin styles
│   │   └── toc.css        # Frontend TOC styles
│   └── js/
│       └── toc.js         # Frontend TOC functionality
├── includes/
│   ├── class-core.php     # Core plugin class
│   ├── class-admin.php    # Admin functionality
│   ├── class-settings.php # Settings handler
│   ├── class-render.php   # TOC rendering
│   └── class-shortcode.php# Shortcode handler
└── languages/
    └── smart-toc.pot      # Translation template
```

## 🔌 Compatibility

Smart TOC works seamlessly with:

- **Page Builders**: Elementor, Beaver Builder, Divi, WPBakery
- **Themes**: Works with any properly coded WordPress theme
- **Caching Plugins**: WP Super Cache, W3 Total Cache, WP Rocket, LiteSpeed Cache
- **SEO Plugins**: Yoast SEO, Rank Math, All in One SEO

## 🚀 Looking for More Features?

**[Smart TOC Pro](https://codecanyon.net/)** extends this plugin with advanced features:

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

[Learn More →](https://codecanyon.net/)

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

- **Documentation**: [Read Documentation](https://github.com/wordpress-plugins-project/smart-table-of-content-free-plugin#readme)
- **WordPress.org**: [Support Forum](https://wordpress.org/support/plugin/smart-toc/)
- **Issues**: [GitHub Issues](https://github.com/wordpress-plugins-project/smart-table-of-content-free-plugin/issues)
- **Developer**: [Anik Chowdhury](https://github.com/anikchowdhurybd)

## 📝 Changelog

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

**Made with ❤️ by [Anik Chowdhury](https://github.com/anikchowdhurybd) for the WordPress community**

If you find this plugin helpful, please consider giving it a ⭐ on GitHub and leaving a review on WordPress.org!
