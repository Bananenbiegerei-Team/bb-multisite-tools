#!/bin/bash
# Installation script for Plugin Update Checker
# Run this if the plugin-update-checker folder is missing

set -e

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PUC_DIR="$PLUGIN_DIR/plugin-update-checker"

echo "BB Multisite Tools - Plugin Update Checker Installation"
echo "========================================================"
echo ""

if [ -d "$PUC_DIR" ]; then
    echo "✓ Plugin Update Checker already installed at: $PUC_DIR"
    echo ""
    read -p "Do you want to update it? (y/n) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Updating Plugin Update Checker..."
        cd "$PUC_DIR"
        git pull
        echo "✓ Updated successfully!"
    else
        echo "Keeping existing installation."
    fi
else
    echo "Installing Plugin Update Checker..."
    cd "$PLUGIN_DIR"
    git clone --depth 1 https://github.com/YahnisElsts/plugin-update-checker.git

    # Clean up unnecessary files
    cd "$PUC_DIR"
    rm -rf .git .gitattributes .gitignore .editorconfig phpcs.xml build examples

    echo "✓ Plugin Update Checker installed successfully!"
fi

echo ""
echo "Installation complete!"
echo ""
echo "Next steps:"
echo "1. Update the GitHub URL in bb-multisite-tools.php (line 18)"
echo "2. Create releases on GitHub to enable updates"
echo "3. See UPDATE-SYSTEM.md for detailed instructions"
