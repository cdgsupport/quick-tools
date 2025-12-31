# CDG Core - Must-Use Plugin

WordPress optimizations, security hardening, and agency features for Crawford Design Group client sites.

## Version 1.1.0

### Installation

1. Upload `cdg-core/` folder to `/wp-content/mu-plugins/`
2. Upload `cdg-core.php` to `/wp-content/mu-plugins/`
3. Visit **Settings → CDG Core** to configure

### Features

- WordPress head cleanup & emoji removal
- Security hardening (XML-RPC, uploads, headers)
- **SVG upload support with automatic sanitization** (new in 1.1.0)
- Performance optimizations (Gutenberg, queries, images)
- Gravity Forms / Divi compatibility fixes
- Documentation system for editors
- CPT Dashboard widgets
- Post type renaming
- Admin branding & custom CSS

### Settings

Visit **Settings → CDG Core** to configure all features.

### SVG Upload Support (v1.1.0)

CDG Core now includes secure SVG upload support. When enabled:

- SVG and SVGZ files can be uploaded through the Media Library
- All uploads are automatically sanitized to remove potentially dangerous content
- SVG previews display correctly in the Media Library

**Security Features:**
- Removes all script tags and JavaScript
- Strips event handler attributes (onclick, onload, onerror, etc.)
- Removes external entity references (XXE protection)
- Filters dangerous elements (embed, object, iframe, foreignObject)
- Validates SVG structure before saving
- Removes PHP and server-side code injections

**Settings:**
- **Enable SVG Uploads**: Allow SVG file uploads (disabled by default)
- **Restrict to Admins**: Only allow administrators to upload SVGs (enabled by default)

Find these settings under **Settings → CDG Core → Security**.

### Post Revisions

Add to `wp-config.php`:

```php
define('WP_POST_REVISIONS', 5);
```

### Changelog

#### 1.1.0
- Added SVG upload support with automatic sanitization
- Added admin-only restriction option for SVG uploads
- Added SVG preview support in Media Library
- Improved security documentation

#### 1.0.0
- Initial release
