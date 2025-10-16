# Quick Start Guide - GitHub Updates

## What Changed?

✅ **Removed:** BB Update Checker (was causing performance issues by checking on every page load)
✅ **Added:** GitHub-based Plugin Update Checker (only checks on WordPress updates page)

## For You (Plugin Developer)

### Step 1: Update GitHub URL
Edit [bb-multisite-tools.php](bb-multisite-tools.php#L22) line 22:
```php
'https://github.com/your-username/bb-multisite-tools/', // Replace with your actual repo
```

### Step 2: Create Releases on GitHub
```bash
# When you want to release an update:
# 1. Update version in bb-multisite-tools.php line 5
# 2. Commit and tag
git add .
git commit -m "Release version 3.9"
git tag v3.9
git push origin main
git push origin v3.9

# 3. Create release on GitHub from this tag
```

### Step 3: Distribute Plugin
When sharing the plugin with other sites:
- The `plugin-update-checker/` folder will NOT be in your repo (it's gitignored)
- Users run `./install-update-checker.sh` to install it
- Or it shows a notice in WP admin with instructions

## For Plugin Users (Installing on Other Sites)

### Option A: Automatic
1. Install plugin (the `plugin-update-checker` folder won't be there)
2. Activate plugin
3. You'll see a notice with instructions
4. Run the command shown, or just run: `./install-update-checker.sh`

### Option B: Manual
```bash
cd /path/to/wp-content/plugins/bb-multisite-tools
git clone --depth 1 https://github.com/YahnisElsts/plugin-update-checker.git
```

## Performance Comparison

**Old BB Update Checker:**
- ❌ Checked on EVERY admin page load
- ❌ Caused noticeable slowdown in backend
- ❌ Multiple API calls per session

**New GitHub Update Checker:**
- ✅ Only checks on WordPress "Updates" page
- ✅ Cached for 12 hours by default
- ✅ No performance impact on regular admin pages
- ✅ Same user experience as WordPress.org plugins

## Private Repository?

If your GitHub repo is private:
1. Create a GitHub Personal Access Token (Settings → Developer settings → PAT)
2. Uncomment line 28 in [bb-multisite-tools.php](bb-multisite-tools.php#L28)
3. Add your token

## Questions?

See [UPDATE-SYSTEM.md](UPDATE-SYSTEM.md) for detailed documentation.
