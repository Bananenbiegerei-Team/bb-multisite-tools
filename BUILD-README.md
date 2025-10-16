# Build & Distribution Guide

## Quick Build

Create a distribution-ready zip file:

```bash
./build.sh
```

Output: `build/dist/bb-multisite-tools-v{version}.zip`

## What Gets Included

### ✓ Core Files
- `bb-multisite-tools.php` - Main plugin file
- `polyfills.php` - Compatibility layer
- `admin.js` - JavaScript functionality
- `style.css` - Plugin styles
- `index.php` - Directory protection

### ✓ Templates
- All files in `templates/` directory
- Database overview, user management, etc.

### ✓ Documentation
- `README.md` - Basic plugin info
- `UPDATE-SYSTEM.md` - Detailed update system documentation
- `QUICK-START.md` - Quick reference guide
- `install-update-checker.sh` - Installation helper script

### ✓ Plugin Update Checker (if present)
- Entire `plugin-update-checker/` library
- Automatically included if the folder exists
- Git files are cleaned from it

## What Gets Excluded

### ✗ Development Files
- `.git/` - Git repository
- `.gitignore`, `.gitattributes` - Git configuration
- `build.sh` - Build script itself
- `build/` - Build directory
- `BUILD-README.md` - This file

### ✗ System Files
- `.DS_Store` - macOS metadata
- Hidden files (`.something`)

### ✗ Editor Files
- `.vscode/`, `.idea/` - Editor configurations
- `*.swp`, `*.swo` - Vim swap files

## Build Process

The build script:

1. **Reads version** from `bb-multisite-tools.php` header
2. **Creates directories**: `build/temp/` and `build/dist/`
3. **Copies files**: Selectively copies only needed files
4. **Cleans up**: Removes unwanted files (`.DS_Store`, git files, etc.)
5. **Creates zip**: Archives everything into versioned zip
6. **Reports**: Shows file size and included/excluded items

## Distribution Workflow

### For GitHub Releases

1. Update version in `bb-multisite-tools.php`
2. Run `./build.sh`
3. Commit and tag: `git tag v3.9 && git push origin v3.9`
4. Create GitHub release
5. Attach `build/dist/bb-multisite-tools-v3.9.zip` to the release

### For Manual Distribution

1. Run `./build.sh`
2. Share `build/dist/bb-multisite-tools-v{version}.zip`
3. Users extract and upload to `wp-content/plugins/`
4. Activate in WordPress admin

### For Git Clone Distribution

1. Push to GitHub
2. Users clone: `git clone https://github.com/you/bb-multisite-tools.git`
3. Users run: `./install-update-checker.sh`
4. Activate in WordPress admin

## Version Management

The version is read from the plugin header:

```php
/*
Plugin Name: BB Multisite Tools for Wikimedia DE
Version: 3.8  <-- Build script reads this
*/
```

**Always update this before building!**

## Build Directory Structure

```
bb-multisite-tools/
├── build/
│   ├── dist/
│   │   └── bb-multisite-tools-v3.8.zip  (final output)
│   └── temp/  (cleaned after build)
│       └── bb-multisite-tools/  (staging)
```

The `build/` directory is gitignored and safe to delete anytime.

## Troubleshooting

**"Permission denied" when running build.sh:**
```bash
chmod +x build.sh
```

**Build creates wrong version number:**
- Check version in `bb-multisite-tools.php` line 5
- Ensure no extra spaces or special characters

**Zip is too large:**
- Check if `plugin-update-checker/` is included (it's ~200KB)
- Look for accidentally included development files

**Missing files in zip:**
- Check the file copy commands in `build.sh`
- Add new files/directories as needed

## Customizing the Build

To add new files to the distribution, edit `build.sh`:

```bash
# Add individual file
cp "$PLUGIN_DIR/new-file.php" "$TEMP_DIR/$PLUGIN_SLUG/"

# Add directory
cp -r "$PLUGIN_DIR/new-directory" "$TEMP_DIR/$PLUGIN_SLUG/"
```

To exclude specific files, they'll automatically be excluded if not explicitly copied.

## CI/CD Integration

The build script is CI-friendly and can be automated:

```yaml
# Example GitHub Actions workflow
- name: Build plugin
  run: ./build.sh

- name: Upload artifact
  uses: actions/upload-artifact@v2
  with:
    name: plugin-zip
    path: build/dist/*.zip
```

## File Size Reference

Typical build sizes:
- Without `plugin-update-checker/`: ~10KB
- With `plugin-update-checker/`: ~200KB
- Both are acceptable for WordPress plugins

## Clean Build

To ensure a completely fresh build:

```bash
rm -rf build/
./build.sh
```

This removes any previous build artifacts before creating a new one.
