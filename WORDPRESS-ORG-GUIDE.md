# WordPress.org Plugin Submission & Approval Guide

> **A battle-tested checklist based on real submission experience with [Anik Smart Table of Contents](https://wordpress.org/plugins/anik-smart-table-of-contents/).**
> Use this guide as a reusable template for every future plugin you submit.

---

## Table of Contents

1. [Before You Start](#1-before-you-start)
2. [Plugin File Structure](#2-plugin-file-structure)
3. [Main Plugin File Header](#3-main-plugin-file-header)
4. [Security Requirements](#4-security-requirements)
5. [Unique Prefix — The #1 Rejection Reason](#5-unique-prefix--the-1-rejection-reason)
6. [Enqueueing Assets Properly](#6-enqueueing-assets-properly)
7. [Internationalization (i18n)](#7-internationalization-i18n)
8. [Data Sanitization, Escaping & Validation](#8-data-sanitization-escaping--validation)
9. [Nonces & Capability Checks](#9-nonces--capability-checks)
10. [uninstall.php — Clean Up After Yourself](#10-uninstallphp--clean-up-after-yourself)
11. [readme.txt — The WordPress.org Standard](#11-readmetxt--the-wordpressorg-standard)
12. [README.md — The GitHub Standard](#12-readmemd--the-github-standard)
13. [PHPCS & WordPress Coding Standards](#13-phpcs--wordpress-coding-standards)
14. [Plugin Check (PCP) Tool](#14-plugin-check-pcp-tool)
15. [Screenshots](#15-screenshots)
16. [SVN & Deployment](#16-svn--deployment)
17. [Common Rejection Reasons & Fixes](#17-common-rejection-reasons--fixes)
18. [Version Bumping Checklist](#18-version-bumping-checklist)
19. [Post-Approval Maintenance](#19-post-approval-maintenance)
20. [Reusable Boilerplate Files](#20-reusable-boilerplate-files)

---

## 1. Before You Start

### Requirements
- A **WordPress.org account** — [Register here](https://login.wordpress.org/register)
- **GPLv2 or later** license (mandatory)
- No premium-only code in the free version (upsell links are OK)
- No external service calls without disclosure and user consent
- No tracking/analytics without **explicit opt-in**
- No "phone home" functionality without disclosure in readme.txt

### Naming Rules
- Plugin slug must be **unique** on WordPress.org
- Use a **distinctive prefix** — NOT generic words like `smart_`, `custom_`, `wp_`
- Your slug becomes permanent after approval (cannot be changed)
- Search [WordPress.org plugins](https://wordpress.org/plugins/) first to avoid name collisions

> **Lesson learned:** We had to rename from `smart-table-of-contents` → `small-seo-engine-smart-toc` → `anik-smart-table-of-contents` across multiple submissions. Pick a unique, distinctive name from day one.

---

## 2. Plugin File Structure

Follow this proven structure for every plugin:

```
your-plugin-slug/
├── your-plugin-slug.php          # Main plugin file (MUST match slug)
├── uninstall.php                 # Cleanup on uninstall
├── index.php                     # Security: "Silence is golden"
├── readme.txt                    # WordPress.org readme (REQUIRED)
├── README.md                     # GitHub readme
├── composer.json                 # Dev dependencies (PHPCS, etc.)
├── phpcs.xml                     # WordPress Coding Standards config
├── .gitignore                    # Git exclusions
├── assets/
│   ├── index.php                 # Security index
│   ├── css/
│   │   ├── index.php
│   │   ├── admin.css             # Admin-only styles
│   │   └── frontend.css          # Frontend styles
│   └── js/
│       ├── index.php
│       ├── admin.js              # Admin-only scripts
│       └── frontend.js           # Frontend scripts
├── includes/
│   ├── index.php                 # Security index
│   ├── class-core.php            # Core/bootstrap class
│   ├── class-admin.php           # Admin functionality
│   ├── class-settings.php        # Settings handler
│   ├── class-render.php          # Frontend rendering
│   └── class-shortcode.php       # Shortcode handler
├── languages/
│   ├── index.php
│   └── your-plugin-slug.pot      # Translation template
└── screenshots/
    ├── screenshot-1.png          # Frontend view
    └── screenshot-2.png          # Admin settings
```

### Key Rules

| Rule | Why |
|------|-----|
| Main PHP file MUST match the plugin slug | WordPress.org requires it |
| Every directory needs an `index.php` | Prevents directory browsing |
| Keep `vendor/` and `node_modules/` out of the submitted zip | Bloat + review issues |
| CSS/JS must be in separate files, NOT inline | WordPress.org compliance |

---

## 3. Main Plugin File Header

This is the **exact format** WordPress.org expects. Every field matters:

```php
<?php
/**
 * Plugin Name: Your Plugin Name
 * Plugin URI: https://yoursite.com/plugins/your-plugin/
 * Description: A clear, one-line description of what your plugin does.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yoursite.com
 * Text Domain: your-plugin-slug
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 */
```

### Critical Notes

- **`Text Domain`** MUST exactly match your plugin slug (folder name)
- **`Domain Path`** must point to your translations directory
- **`Requires at least`** — the minimum WP version you support
- **`Tested up to`** — the latest WP version you've tested against
- **`Requires PHP`** — minimum PHP version (7.4+ recommended in 2025/2026)
- **`License`** — MUST be GPLv2 or later
- The header format must use `Plugin Name:` NOT `Plugin name:` — capitalize exactly as shown

> **Lesson learned:** WordPress.org Plugin Check is strict about the header format. Even minor formatting differences (like extra spaces or inconsistent capitalization) can trigger warnings.

---

## 4. Security Requirements

### Direct Access Prevention

**Every PHP file** must start with this:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
```

### Security Index Files

Every directory gets an `index.php`:

```php
<?php
// Silence is golden.
```

### Define Plugin Constants Safely

```php
// Prevent redefinition conflicts
define( 'YOURPREFIX_VERSION', '1.0.0' );
define( 'YOURPREFIX_PATH', plugin_dir_path( __FILE__ ) );
define( 'YOURPREFIX_URL', plugin_dir_url( __FILE__ ) );
define( 'YOURPREFIX_BASENAME', plugin_basename( __FILE__ ) );
```

---

## 5. Unique Prefix — The #1 Rejection Reason

WordPress.org **WILL reject** your plugin if you use generic prefixes. Every global function, class, constant, option, hook, shortcode, and meta key must use a **unique prefix**.

### What Needs Prefixing

| Element | ❌ Bad | ✅ Good |
|---------|--------|---------|
| Functions | `toc_render()` | `aniksmta_render()` |
| Classes | `TOC_Core` | `Aniksmta_Core` |
| Constants | `TOC_VERSION` | `ANIKSMTA_VERSION` |
| Options | `toc_settings` | `aniksmta_settings` |
| Transients | `toc_cache` | `aniksmta_cache` |
| Post Meta | `_disable_toc` | `_aniksmta_disable` |
| Shortcodes | `[toc]` | `[aniksmta_toc]` |
| Hooks | `toc_before_render` | `aniksmta_before_render` |
| Enqueue handles | `toc-css` | `aniksmta-css` |
| Nonce actions | `save_settings` | `aniksmta_save_settings` |
| Admin pages | `toc-settings` | `aniksmta-settings` |

### Choosing Your Prefix

Pick something **4-8 characters** that's unique to you:

- Company/brand abbreviation: `aniksmta` (Anik Smart Table of Contents)
- Initials + project: `acstoc` (AC's Smart TOC)
- Brand name shorthand: `ssengine` (Small SEO Engine)

### PHPCS Enforces This Automatically

In your `phpcs.xml`:

```xml
<rule ref="WordPress.NamingConventions.PrefixAllGlobals">
    <properties>
        <property name="prefixes" type="array">
            <element value="yourprefix"/>
            <element value="Yourprefix"/>
            <element value="YOURPREFIX"/>
        </property>
    </properties>
</rule>
```

This will flag any un-prefixed global symbols during linting.

---

## 6. Enqueueing Assets Properly

**NEVER use inline `<style>` or `<script>` tags.** WordPress.org will reject this.

### Frontend Assets

```php
add_action( 'wp_enqueue_scripts', 'yourprefix_enqueue_frontend' );
function yourprefix_enqueue_frontend() {
    // Only load where needed
    if ( ! is_singular() ) {
        return;
    }

    wp_enqueue_style(
        'yourprefix-frontend',                              // Unique handle
        YOURPREFIX_URL . 'assets/css/frontend.css',         // File path
        array(),                                             // Dependencies
        YOURPREFIX_VERSION                                   // Version for cache busting
    );

    wp_enqueue_script(
        'yourprefix-frontend',
        YOURPREFIX_URL . 'assets/js/frontend.js',
        array(),
        YOURPREFIX_VERSION,
        true                                                 // Load in footer
    );

    // Pass PHP data to JavaScript safely
    wp_localize_script( 'yourprefix-frontend', 'yourprefixData', array(
        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'yourprefix_nonce' ),
        'settings' => array(
            'smoothScroll' => true,
            'offset'       => 80,
        ),
    ) );
}
```

### Admin Assets (Only on YOUR Pages)

```php
add_action( 'admin_enqueue_scripts', 'yourprefix_enqueue_admin' );
function yourprefix_enqueue_admin( $hook ) {
    // IMPORTANT: Only load on your own admin pages
    if ( 'settings_page_yourprefix-settings' !== $hook ) {
        return;
    }

    wp_enqueue_style(
        'yourprefix-admin',
        YOURPREFIX_URL . 'assets/css/admin.css',
        array(),
        YOURPREFIX_VERSION
    );

    wp_enqueue_script(
        'yourprefix-admin',
        YOURPREFIX_URL . 'assets/js/admin.js',
        array( 'jquery', 'wp-color-picker' ),
        YOURPREFIX_VERSION,
        true
    );
}
```

### Key Rules

- Use `YOURPREFIX_VERSION` constant for cache busting on every update
- Load frontend scripts in the **footer** (`true` as last parameter)
- **Conditionally load** — don't load admin assets on frontend or vice versa
- Don't load assets on pages where your plugin isn't active

---

## 7. Internationalization (i18n)

### Load Text Domain

```php
add_action( 'init', 'yourprefix_load_textdomain' );
function yourprefix_load_textdomain() {
    load_plugin_textdomain(
        'your-plugin-slug',                                   // Text domain = slug
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages' // Path
    );
}
```

### Wrap All User-Facing Strings

```php
// Simple string
__( 'Table of Contents', 'your-plugin-slug' )

// Echoed string
esc_html_e( 'Settings saved.', 'your-plugin-slug' );

// String with variables
printf(
    /* translators: %s: plugin name */
    esc_html__( 'Thank you for using %s!', 'your-plugin-slug' ),
    'Your Plugin Name'
);

// Pluralization
printf(
    /* translators: %d: number of items */
    esc_html( _n( '%d item', '%d items', $count, 'your-plugin-slug' ) ),
    $count
);
```

### Generate the .pot File

```bash
# Using WP-CLI
wp i18n make-pot . languages/your-plugin-slug.pot --slug=your-plugin-slug

# Or with the wp-cli i18n command
wp i18n make-pot . languages/your-plugin-slug.pot --domain=your-plugin-slug
```

### Common Mistakes
- Text domain doesn't match plugin slug → **rejection**
- Missing `/* translators: */` comments for strings with placeholders → PHPCS warning
- Forgetting to escape translated strings → security issue

---

## 8. Data Sanitization, Escaping & Validation

This is the area where **most plugins get rejected**. Follow this strictly.

### The Golden Rule

> **Sanitize INPUT, Escape OUTPUT, Validate DATA**

### Input Sanitization

```php
// Text input
$title = sanitize_text_field( wp_unslash( $_POST['title'] ) );

// Textarea
$content = sanitize_textarea_field( wp_unslash( $_POST['content'] ) );

// Email
$email = sanitize_email( $_POST['email'] );

// URL
$url = esc_url_raw( $_POST['url'] );

// Integer
$number = absint( $_POST['number'] );

// Array of integers
$ids = array_map( 'absint', (array) $_POST['ids'] );

// Checkbox (boolean)
$enabled = isset( $_POST['enabled'] ) ? true : false;

// Color hex code
$color = sanitize_hex_color( $_POST['color'] );
```

### Output Escaping

```php
// In HTML context
echo esc_html( $value );

// In HTML attributes
echo esc_attr( $value );

// URLs
echo esc_url( $url );

// JavaScript
echo esc_js( $value );

// In textarea
echo esc_textarea( $value );

// When HTML is intentionally allowed
echo wp_kses_post( $content );

// Escaped translation
echo esc_html__( 'Hello', 'your-plugin-slug' );
esc_html_e( 'Hello', 'your-plugin-slug' );
echo esc_attr__( 'Click here', 'your-plugin-slug' );
```

### Complete Settings Save Example

```php
function yourprefix_save_settings() {
    // 1. Check nonce
    if ( ! isset( $_POST['yourprefix_nonce'] )
         || ! wp_verify_nonce(
             sanitize_text_field( wp_unslash( $_POST['yourprefix_nonce'] ) ),
             'yourprefix_save_settings'
         )
    ) {
        wp_die( esc_html__( 'Security check failed.', 'your-plugin-slug' ) );
    }

    // 2. Check capability
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'your-plugin-slug' ) );
    }

    // 3. Sanitize each field
    $settings = array(
        'enabled'      => isset( $_POST['enabled'] ),
        'title'        => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
        'min_headings' => absint( $_POST['min_headings'] ?? 2 ),
        'theme_color'  => sanitize_hex_color( $_POST['theme_color'] ?? '#0073aa' ),
        'post_types'   => array_map(
            'sanitize_text_field',
            wp_unslash( (array) ( $_POST['post_types'] ?? array() ) )
        ),
    );

    // 4. Save
    update_option( 'yourprefix_settings', $settings );

    // 5. Redirect with success message
    wp_safe_redirect(
        add_query_arg( 'settings-updated', 'true', wp_get_referer() )
    );
    exit;
}
```

---

## 9. Nonces & Capability Checks

### Creating Nonces

```php
// In a form
wp_nonce_field( 'yourprefix_save_settings', 'yourprefix_nonce' );

// In a URL
$url = wp_nonce_url( $action_url, 'yourprefix_action', 'yourprefix_nonce' );

// For AJAX
wp_create_nonce( 'yourprefix_ajax_nonce' );
```

### Verifying Nonces

```php
// Form submission
if ( ! isset( $_POST['yourprefix_nonce'] )
     || ! wp_verify_nonce(
         sanitize_text_field( wp_unslash( $_POST['yourprefix_nonce'] ) ),
         'yourprefix_save_settings'
     )
) {
    wp_die( esc_html__( 'Security check failed.', 'your-plugin-slug' ) );
}

// AJAX
check_ajax_referer( 'yourprefix_ajax_nonce', 'nonce' );
```

### Capability Checks

```php
// Admin settings — require manage_options
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Unauthorized.', 'your-plugin-slug' ) );
}

// Post meta — require edit_post
if ( ! current_user_can( 'edit_post', $post_id ) ) {
    return;
}
```

> **Every form submission, AJAX handler, and admin action MUST have both a nonce check AND a capability check.**

---

## 10. uninstall.php — Clean Up After Yourself

WordPress.org **requires** that your plugin cleans up its data on uninstall. Create `uninstall.php` in your plugin root:

```php
<?php
/**
 * Uninstall handler
 *
 * @package Your_Plugin
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options
delete_option( 'yourprefix_settings' );
delete_option( 'yourprefix_install_date' );
delete_option( 'yourprefix_version' );

// Delete all post meta created by the plugin
delete_post_meta_by_key( '_yourprefix_disable' );
delete_post_meta_by_key( '_yourprefix_custom_setting' );

// Clear cached data
delete_transient( 'yourprefix_cache' );

// If your plugin created custom database tables (rare for simple plugins):
// global $wpdb;
// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}yourprefix_table" );
```

### Rules
- Use `WP_UNINSTALL_PLUGIN` check, NOT `ABSPATH`
- Delete **ALL** options, transients, post meta, and custom tables
- Do NOT use `register_uninstall_hook()` — use the file approach instead
- Test this manually: uninstall your plugin and check the database

---

## 11. readme.txt — The WordPress.org Standard

This file is **parsed by WordPress.org** to generate your plugin page. Format matters enormously.

### Template

```
=== Your Plugin Name ===
Contributors: your-wporg-username
Donate link: https://yoursite.com/donate
Tags: tag1, tag2, tag3, tag4, tag5
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A clear, one-line description of your plugin (max ~150 characters).

== Description ==

Full description of your plugin. Markdown is supported.

**Bold text** for emphasis.

= Feature List =
* Feature 1 — description
* Feature 2 — description

== Installation ==

1. Upload the `your-plugin-slug` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings → Your Plugin to configure

== Frequently Asked Questions ==

= How do I use the shortcode? =

Use `[yourprefix_shortcode]` in your post content.

== Screenshots ==

1. Description of screenshot 1
2. Description of screenshot 2

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of Your Plugin Name.
```

### Critical Rules

| Rule | Detail |
|------|--------|
| `Stable tag` | Must match the version in your main PHP file header **exactly** |
| `Contributors` | Must be valid WordPress.org usernames |
| `Tags` | Max 5 tags, comma-separated |
| `Tested up to` | Must be a released WordPress version |
| Screenshots | Numbered `screenshot-1.png`, `screenshot-2.png` in `assets/` for SVN or `screenshots/` for development |
| Changelog | Required — list changes for every version |
| Upgrade Notice | Recommended — brief note per version |

### Validating readme.txt

Use the official validator: **https://wordpress.org/plugins/developers/readme-validator/**

Paste your entire readme.txt content and fix all errors before submitting.

---

## 12. README.md — The GitHub Standard

This renders on your **GitHub repository** page. Different from `readme.txt`.

### What to Include

- Badges (version, WordPress tested, PHP, license)
- Screenshots/GIFs
- Feature list
- Installation (including `git clone`)
- Configuration table
- File structure diagram
- Usage examples (shortcodes, functions)
- Contributing guidelines
- License info
- Support links

### Badge Examples

```markdown
[![WordPress Plugin Version](https://img.shields.io/badge/version-1.0.0-blue)]()
[![WordPress Tested](https://img.shields.io/badge/WordPress-6.9%20tested-brightgreen)]()
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple)]()
[![License](https://img.shields.io/badge/license-GPLv2-orange)]()
```

---

## 13. PHPCS & WordPress Coding Standards

### Setup

```json
// composer.json
{
    "name": "your-vendor/your-plugin-slug",
    "description": "Your Plugin Description",
    "type": "wordpress-plugin",
    "license": "GPL-2.0-or-later",
    "require": {
        "php": ">=7.4"
    },
    "require-dev": {
        "squizlabs/php_codesniffer": "^3.7",
        "wp-coding-standards/wpcs": "^3.0",
        "phpcompatibility/phpcompatibility-wp": "^2.1",
        "php-stubs/wordpress-stubs": "^6.4"
    },
    "config": {
        "allow-plugins": {
            "dealerdirect/phpcodesniffer-composer-installer": true
        }
    },
    "scripts": {
        "phpcs": "phpcs",
        "phpcbf": "phpcbf"
    }
}
```

### PHPCS Configuration

```xml
<?xml version="1.0"?>
<ruleset name="Your Plugin Name">
    <description>PHPCS ruleset for Your Plugin</description>

    <!-- Text domain must match slug -->
    <config name="text_domain" value="your-plugin-slug"/>
    <config name="minimum_supported_wp_version" value="5.0"/>

    <arg name="extensions" value="php"/>
    <arg value="ps"/>
    <file>.</file>

    <!-- Exclude non-plugin files -->
    <exclude-pattern>/vendor/*</exclude-pattern>
    <exclude-pattern>/node_modules/*</exclude-pattern>
    <exclude-pattern>/.git/*</exclude-pattern>

    <!-- WordPress Coding Standards -->
    <rule ref="WordPress">
        <!-- Allow modern PHP short array syntax -->
        <exclude name="Generic.Arrays.DisallowShortArraySyntax"/>
        <!-- Relax strict comment requirements -->
        <exclude name="Squiz.Commenting.FileComment.Missing"/>
        <exclude name="Squiz.Commenting.FileComment.MissingPackageTag"/>
        <exclude name="Squiz.Commenting.FunctionComment.MissingParamTag"/>
    </rule>

    <!-- Allow flexible file naming -->
    <rule ref="WordPress.Files.FileName">
        <properties>
            <property name="strict_class_file_names" value="false"/>
        </properties>
    </rule>

    <!-- Enforce YOUR unique prefix -->
    <rule ref="WordPress.NamingConventions.PrefixAllGlobals">
        <properties>
            <property name="prefixes" type="array">
                <element value="yourprefix"/>
                <element value="Yourprefix"/>
                <element value="YOURPREFIX"/>
            </property>
        </properties>
    </rule>
</ruleset>
```

### Running PHPCS

```bash
# Install dependencies
composer install

# Run code sniffer
composer phpcs

# Auto-fix what's possible
composer phpcbf
```

### Fix All Issues Before Submitting

Run PHPCS until you get **zero errors**. Warnings are OK to submit with, but errors will cause rejection.

---

## 14. Plugin Check (PCP) Tool

WordPress.org uses the **Plugin Check** plugin to automatically scan submissions.

### Install & Run

1. Install the [Plugin Check](https://wordpress.org/plugins/plugin-check/) plugin on your dev site
2. Go to **Tools → Plugin Check**
3. Select your plugin
4. Run the check
5. Fix ALL errors (warnings are advisory)

### Common PCP Failures

| Issue | Fix |
|-------|-----|
| Inline CSS/JS | Move to external files and `wp_enqueue_*` |
| Missing ABSPATH check | Add `if ( ! defined( 'ABSPATH' ) ) exit;` |
| Generic prefix | Rename all globals to use unique prefix |
| Direct file access possible | Add `index.php` to all directories |
| Incorrect text domain | Text domain must match plugin slug exactly |
| Calling `file_get_contents()` | Use `wp_remote_get()` for URLs, `WP_Filesystem` for files |
| Using `$_GET`/`$_POST` without sanitization | Wrap in `sanitize_text_field( wp_unslash() )` |
| Unescaped output | Wrap all `echo` output in `esc_html()`, `esc_attr()`, etc. |

---

## 15. Screenshots

### For WordPress.org (SVN `assets/` folder)

These go in the **SVN `assets/` directory** (not the plugin's `assets/` folder):

```
assets/
├── banner-772x250.png          # Plugin page banner (required for good presentation)
├── banner-1544x500.png         # HiDPI banner (optional but recommended)
├── icon-128x128.png            # Plugin icon
├── icon-256x256.png            # HiDPI plugin icon
├── screenshot-1.png            # Must match readme.txt numbering
├── screenshot-2.png
└── screenshot-3.png
```

### Sizes

| Asset | Size | Required |
|-------|------|----------|
| Banner | 772 × 250 px | Recommended |
| Banner HiDPI | 1544 × 500 px | Optional |
| Icon | 128 × 128 px | Recommended |
| Icon HiDPI | 256 × 256 px | Optional |
| Screenshots | Any reasonable size | At least 1 recommended |

### Tips
- Use PNG format for clear text/UI, JPG for photos
- Keep file sizes under 1MB each
- Screenshots should show your plugin in action (frontend + admin)
- Banner should be branded and professional

---

## 16. SVN & Deployment

### After Approval

WordPress.org will email you SVN access credentials. Here's how to deploy:

```bash
# 1. Checkout the SVN repository
svn checkout https://plugins.svn.wordpress.org/your-plugin-slug/ svn-your-plugin

# 2. Navigate into it
cd svn-your-plugin

# 3. The structure will be:
#    /trunk/       ← Your current development code
#    /tags/        ← Tagged releases (1.0.0, 1.0.1, etc.)
#    /assets/      ← Banners, icons, screenshots for WordPress.org
```

### Deploying a New Version

```bash
# 1. Copy your plugin files to trunk (delete old files first)
rm -rf trunk/*
cp -r /path/to/your-plugin/* trunk/

# 2. Add banners/icons to assets (first time only)
cp banner-772x250.png assets/
cp icon-128x128.png assets/

# 3. Tag the release
svn cp trunk tags/1.0.0

# 4. Add new files to SVN tracking
svn add --force trunk/
svn add --force tags/1.0.0/
svn add --force assets/

# 5. Check status
svn status

# 6. Commit
svn commit -m "Release version 1.0.0" --username your-wporg-username
```

### What NOT to Include in `trunk/`
- `.git/` directory
- `node_modules/`
- `vendor/` (unless your plugin needs it at runtime)
- `.gitignore`
- `composer.json` / `composer.lock`
- `phpcs.xml`
- `README.md` (WordPress.org uses readme.txt)
- `.vscode/` or any IDE files
- Test files

### Automating with GitHub Actions (Optional)

You can set up GitHub Actions to auto-deploy to SVN on tagged releases. Search for `10up/action-wordpress-plugin-deploy` on GitHub.

---

## 17. Common Rejection Reasons & Fixes

Based on real WordPress.org review feedback:

### 1. Generic Function/Class Names
**Rejection email:** *"Please use a unique prefix for all public-facing functions, classes, and variables."*

**Fix:** Rename everything. Use `yourprefix_` for functions, `Yourprefix_` for classes, `YOURPREFIX_` for constants.

### 2. Inline CSS and JavaScript
**Rejection email:** *"Please enqueue all scripts and styles via wp_enqueue_script/wp_enqueue_style."*

**Fix:** Move ALL inline `<style>` and `<script>` blocks to external `.css` and `.js` files. Enqueue them properly.

### 3. Sanitization/Escaping Issues
**Rejection email:** *"Data must be sanitized on input and escaped on output."*

**Fix:** Audit every `$_POST`, `$_GET`, `$_REQUEST` usage. Add `sanitize_text_field( wp_unslash() )` on input. Add `esc_html()`, `esc_attr()` on ALL output.

### 4. Calling External Services Without Disclosure
**Rejection email:** *"Your plugin makes outbound requests. Please disclose this."*

**Fix:** Add a section in readme.txt describing what data is sent where and why. Provide a privacy policy link.

### 5. Including Compressed/Minified JS Without Source
**Rejection email:** *"Please include the unminified/developer version of included scripts."*

**Fix:** Include **both** `.js` and `.min.js` files, OR only include the readable source. Never submit only minified code.

### 6. Incorrect Stable Tag
**Rejection email:** *"Stable tag in readme.txt does not match the plugin version."*

**Fix:** `Stable tag` in readme.txt MUST match `Version` in your main plugin file header.

### 7. Using WordPress Trademark Improperly
**Rejection email:** *"Plugin names may not start with 'WordPress' or 'WP'."*

**Fix:** Don't start your plugin name with "WordPress", "WP", or "Gutenberg".

---

## 18. Version Bumping Checklist

When releasing a new version, update **ALL** of these:

```
☐ Main plugin file header:     Version: X.Y.Z
☐ Plugin constant:             define( 'YOURPREFIX_VERSION', 'X.Y.Z' );
☐ readme.txt:                  Stable tag: X.Y.Z
☐ readme.txt Changelog:        = X.Y.Z = section with changes
☐ readme.txt Upgrade Notice:   = X.Y.Z = brief description
☐ README.md Changelog:         ### X.Y.Z section
☐ README.md badges:            version-X.Y.Z
☐ "Tested up to":              Update if new WP version released
```

### Semantic Versioning

- **Patch** (1.0.0 → 1.0.1): Bug fixes, minor tweaks
- **Minor** (1.0.0 → 1.1.0): New features, backward compatible
- **Major** (1.0.0 → 2.0.0): Breaking changes

---

## 19. Post-Approval Maintenance

### Respond to Support Threads
- WordPress.org tracks response rate
- Aim to respond within 1-2 business days
- Be professional and helpful

### Monitor Reviews
- Thank positive reviewers
- Address negative reviews constructively
- Fix reported bugs promptly

### Keep Updated
- Test with every major WordPress release
- Update `Tested up to` in readme.txt
- Release patch versions for compatibility

### Track Stats
- WordPress.org provides download stats
- Monitor active installs
- Watch for support threads about new issues

---

## 20. Reusable Boilerplate Files

### .gitignore

```gitignore
# Virtual environments
.venv/
venv/
env/

# IDE / Editor
.vscode/
.idea/
*.sublime-project
*.sublime-workspace

# OS files
.DS_Store
Thumbs.db

# Dev-only files
phpunit.xml
.phpcs.xml
.phpunit.xml
composer.lock

# Build artifacts
node_modules/
vendor/
*.log

# Temporary files
*.tmp
*.bak
*.swp
```

### Activation Hook Pattern

```php
register_activation_hook( __FILE__, 'yourprefix_activate' );
function yourprefix_activate() {
    if ( ! get_option( 'yourprefix_settings' ) ) {
        $defaults = array(
            'enabled'    => true,
            'post_types' => array( 'post', 'page' ),
            // ... your defaults
        );
        update_option( 'yourprefix_settings', $defaults );
    }
    // Store install date for review requests, etc.
    if ( ! get_option( 'yourprefix_install_date' ) ) {
        update_option( 'yourprefix_install_date', time() );
    }
}
```

### Deactivation Hook Pattern

```php
register_deactivation_hook( __FILE__, 'yourprefix_deactivate' );
function yourprefix_deactivate() {
    // Clear transients and temporary data
    delete_transient( 'yourprefix_cache' );
    // Do NOT delete settings here — that's for uninstall.php
}
```

### Singleton Core Class Pattern

```php
class Yourprefix_Core {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init();
    }

    private function load_dependencies() {
        require_once YOURPREFIX_PATH . 'includes/class-settings.php';
        require_once YOURPREFIX_PATH . 'includes/class-render.php';
        require_once YOURPREFIX_PATH . 'includes/class-admin.php';
    }

    private function init() {
        // Frontend always
        new Yourprefix_Render();

        // Admin only
        if ( is_admin() ) {
            new Yourprefix_Admin();
        }
    }
}
```

---

## Quick-Start Checklist for New Plugins

Copy this checklist for every new plugin submission:

```
PRE-SUBMISSION
☐ Unique, distinctive plugin name chosen
☐ Unique prefix established (4-8 chars)
☐ Main file name matches intended slug
☐ Plugin header has ALL required fields
☐ Text Domain matches slug exactly
☐ ABSPATH check in every PHP file
☐ index.php in every directory
☐ All assets in external files (no inline CSS/JS)
☐ All assets properly enqueued with wp_enqueue_*
☐ All input sanitized (sanitize_text_field, absint, etc.)
☐ All output escaped (esc_html, esc_attr, esc_url)
☐ Nonces on all forms and AJAX handlers
☐ Capability checks on all admin actions
☐ uninstall.php created and tested
☐ Text domain used on all user-facing strings
☐ .pot file generated
☐ readme.txt created and validated
☐ Changelog filled in
☐ Screenshots prepared
☐ PHPCS passes with zero errors
☐ Plugin Check (PCP) passes with zero errors
☐ Tested on latest WordPress version
☐ Tested on minimum PHP version
☐ Tested activation, deactivation, and uninstall
☐ No vendor/node_modules in submission zip

SUBMISSION
☐ Submit at: https://wordpress.org/plugins/developers/add/
☐ Upload zip file (or provide SVN URL)
☐ Wait for review (typically 1-7 business days)
☐ Respond to any reviewer feedback promptly
☐ Fix and resubmit if requested

POST-APPROVAL
☐ Set up SVN deployment workflow
☐ Upload banners, icons to SVN assets/
☐ Tag first release in SVN
☐ Monitor support forum
☐ Set up GitHub Actions for auto-deploy (optional)
```

---

## Useful Links

| Resource | URL |
|----------|-----|
| Submit a Plugin | https://wordpress.org/plugins/developers/add/ |
| Readme Validator | https://wordpress.org/plugins/developers/readme-validator/ |
| Plugin Guidelines | https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/ |
| Plugin Check (PCP) | https://wordpress.org/plugins/plugin-check/ |
| WordPress Coding Standards | https://developer.wordpress.org/coding-standards/wordpress-coding-standards/ |
| Plugin Handbook | https://developer.wordpress.org/plugins/ |
| SVN Guide | https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/ |
| Auto-Deploy Action | https://github.com/10up/action-wordpress-plugin-deploy |
| PHPCS WP Rules | https://github.com/WordPress/WordPress-Coding-Standards |

---

*Last updated: February 2026 — Based on Anik Smart Table of Contents v1.0.9 submission experience.*
