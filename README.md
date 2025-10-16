# BB Multisite Tools for Wikimedia DE

WordPress multisite management plugin providing essential tools for user management, database monitoring, plugin/theme tracking, and more.

## Features

- **Database Overview** - Monitor table sizes and rows with sortable columns
- **User Management** - Manage users and roles across network sites
- **Plugin/Theme Usage** - Track which sites use which themes and plugins
- **ACF Blocks Tracking** - See where ACF blocks are used
- **WPForms Management** - Track form usage across sites
- **Data Cleanup** - Clean production data for development (non-production only)
- **Orphaned Tables** - Identify and remove database tables from deleted sites

## Installation

### From Zip (Recommended)

1. Download the latest release zip
2. Upload to `wp-content/plugins/`
3. Extract and activate in Network Admin → Plugins

### From Git Repository

```bash
cd wp-content/plugins/
git clone https://github.com/your-username/bb-multisite-tools.git
cd bb-multisite-tools
./install-update-checker.sh
```

Activate in Network Admin → Plugins

## Automatic Updates

This plugin uses GitHub releases for automatic updates without performance overhead.

### Setup Updates (Plugin Developer)

1. **Update GitHub URL** in `bb-multisite-tools.php` line 21:
   ```php
   'https://github.com/your-username/bb-multisite-tools/'
   ```

2. **Create releases** when deploying:
   ```bash
   # Update version in bb-multisite-tools.php header
   ./build.sh  # Create distribution zip
   git add .
   git commit -m "Release v3.9"
   git tag v3.9
   git push origin main --tags
   ```

3. **Create GitHub release** from tag, optionally attach the build zip

### Private Repository

For private repos, uncomment line 27 and add your GitHub token:
```php
$bbMultisiteToolsUpdateChecker->setAuthentication('ghp_your_token_here');
```

Generate token at: GitHub Settings → Developer settings → Personal access tokens (needs `repo` scope)

### Update Performance

- ✅ Checks **only** on WordPress Updates page
- ✅ Cached for 12 hours
- ✅ No page load performance impact
- ❌ Old BB Update Checker removed (checked every page load)

## Build System

Create distribution-ready zip with only necessary files:

```bash
./build.sh
```

**Output:** `build/dist/bb-multisite-tools-v{version}.zip` (~200KB)

**Includes:** Core files, templates, Plugin Update Checker library, documentation
**Excludes:** Git files, build scripts, dev files, `.DS_Store`

**Workflow:**
1. Update version in plugin header
2. Run `./build.sh`
3. Commit and tag
4. Create GitHub release
5. Attach build zip (optional - for manual installs)

## Configuration

### Network Admin Menu

Access via: **Network Admin → BB Multisite Tools**

- **Info** - Plugin information and server status
- **Themes** - Theme usage across sites
- **ACF Blocks Usage** - Track ACF block implementations
- **Users & Roles** - User management and role assignments
- **Database** - Table sizes with sortable columns (click headers to sort)
- **WPForms** - Form tracking and recipient management
- **Fix Permalinks** - Refresh rewrite rules for all sites

### Development-Only Features

Available when `wp_get_environment_type() !== 'production'`:

- **Adjust Network Sites** - Update site domains/paths
- **Cleanup Prod Data** - Remove production data for dev environments

## Requirements

- WordPress 5.3+
- PHP 5.5+
- WordPress Multisite Network

## Development

### File Structure

```
bb-multisite-tools/
├── bb-multisite-tools.php    # Main plugin file
├── admin.js                   # JavaScript (includes table sorting)
├── style.css                  # Admin styles
├── polyfills.php              # PHP compatibility
├── templates/                 # Page templates
├── plugin-update-checker/     # Update library (auto-installed)
├── build.sh                   # Distribution builder
└── install-update-checker.sh  # Dependency installer
```

### Adding New Features

1. Add menu page in `setup_menu_pages()` method
2. Create page method (e.g., `new_feature_page()`)
3. Create template in `templates/new_feature.php`
4. Add styles to `style.css` if needed
5. Add JavaScript to `admin.js` if needed

### Table Sorting Feature

Database tables are sortable by clicking column headers:
- **Rows** - Initially sorted descending (largest first)
- **Total Size** - Numeric sorting
- **Table/Site** - Alphabetic sorting
- Click header to toggle ascending/descending

Implementation: Lines 142-212 in `admin.js`

## Troubleshooting

### "Plugin Update Checker library is missing"

**Solution:**
```bash
cd wp-content/plugins/bb-multisite-tools
./install-update-checker.sh
```

Or manually:
```bash
git clone --depth 1 https://github.com/YahnisElsts/plugin-update-checker.git
```

### Updates Not Showing

- Verify GitHub URL is correct (line 21)
- Ensure you created a GitHub **release** (not just a tag)
- Check: Network Admin → Updates
- Force check: delete transients via plugin like Transients Manager

### Performance Issues

This plugin now uses efficient GitHub-based updates. If experiencing slowness:
- Ensure old BB Update Checker is disabled
- Check Database tab for large tables
- Use Cleanup Prod Data on development sites

### Syntax Errors

If seeing parse errors after update:
- Clear OpCache: `opcache_reset()`
- Restart PHP-FPM: `sudo service php-fpm restart`
- Verify PHP version meets requirements (5.5+)

## Changelog

### 3.8
- Added sortable columns to database table overview
- Removed BB Update Checker (performance improvement)
- Added GitHub-based automatic updates
- Added build system for clean distributions
- Improved admin UI styling

## License

Proprietary - Wikimedia Deutschland e.V.

## Credits

- **Author:** Eric Leclercq
- **Organization:** Wikimedia Deutschland
- **Update System:** [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) by Yahnis Elsts

## Support

For issues or questions:
1. Check this README
2. Review GitHub Issues
3. Contact the development team

---

**Note:** This plugin is designed for WordPress multisite networks and provides powerful administrative tools. Always test updates on a staging environment first.
