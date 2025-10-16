# GitHub-Based Update System

This plugin uses the [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) library to provide automatic updates from GitHub.

## Benefits Over BB Update Checker

- **No performance impact**: Only checks for updates on the WordPress updates page (not on every page load)
- **Lightweight**: Uses GitHub's API efficiently
- **Version control integration**: Updates tied directly to your Git releases
- **Transparent**: Users can see changelogs and release notes from GitHub

## Setup Instructions

### 1. Create a GitHub Repository (if not already done)

```bash
cd "/path/to/bb-multisite-tools"
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/your-username/bb-multisite-tools.git
git push -u origin main
```

### 2. Update the GitHub URL in the Plugin

Edit `bb-multisite-tools.php` line 18 and replace:
```php
'https://github.com/your-username/bb-multisite-tools/'
```
with your actual GitHub repository URL.

### 3. Create Releases on GitHub

When you want to release an update:

1. Update the version number in `bb-multisite-tools.php` header (line 5)
2. Commit your changes
3. Create a Git tag:
   ```bash
   git tag v3.9
   git push origin v3.9
   ```
4. Go to GitHub → Releases → Create a new release
5. Select the tag (v3.9)
6. Add release notes
7. Publish the release

### 4. For Private Repositories (Optional)

If your repository is private, you need to:

1. Create a GitHub Personal Access Token:
   - Go to GitHub Settings → Developer settings → Personal access tokens
   - Create token with `repo` scope

2. Uncomment and update line 24 in `bb-multisite-tools.php`:
   ```php
   $bbMultisiteToolsUpdateChecker->setAuthentication('your-github-token-here');
   ```

### 5. Installation on Other Sites

When installing on other WordPress sites:

1. Upload the plugin folder (without the `plugin-update-checker` directory - it's in .gitignore)
2. The plugin will automatically clone the Plugin Update Checker on first load
3. Updates will appear in WordPress admin → Updates

**Note**: The `plugin-update-checker` folder should NOT be committed to your repository. It will be automatically downloaded when needed.

## Building a Distribution Package

To create a clean distribution zip for deployment:

```bash
./build.sh
```

This creates `build/dist/bb-multisite-tools-v{version}.zip` containing:
- ✓ All necessary plugin files (PHP, JS, CSS, templates)
- ✓ Plugin Update Checker library (if installed locally)
- ✓ Documentation and installation script
- ✗ Excludes: Git files, build scripts, and development files

The generated zip is ready to:
- Upload to other WordPress installations
- Attach as a release asset on GitHub
- Distribute to team members

**Tip**: You can attach this zip to your GitHub release for users who prefer manual installation.

## How It Works

- WordPress checks for updates once per day (configurable)
- The Plugin Update Checker queries GitHub's API for the latest release
- If a new version is available, it appears in the WordPress updates page
- Users can update with one click, just like WordPress.org plugins

## Troubleshooting

**"Plugin Update Checker not found" error:**
- Make sure the `plugin-update-checker` folder exists
- Run: `cd plugin-folder && git clone https://github.com/YahnisElsts/plugin-update-checker.git`

**Updates not showing:**
- Check that you've created a GitHub release (not just a tag)
- Verify the GitHub URL in line 18 is correct
- Check WordPress Site Health for API connection issues

**Private repo access issues:**
- Verify your GitHub token has `repo` scope
- Make sure the token is added on line 24
- Test the token: `curl -H "Authorization: token YOUR_TOKEN" https://api.github.com/user`
