#!/bin/bash
# Build script for BB Multisite Tools
# Creates a clean distribution zip with only necessary files

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get plugin directory and version
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_SLUG="bb-multisite-tools"
VERSION=$(grep "^Version:" "$PLUGIN_DIR/bb-multisite-tools.php" | awk '{print $2}' | tr -d '\r')
BUILD_DIR="$PLUGIN_DIR/build"
DIST_DIR="$BUILD_DIR/dist"
TEMP_DIR="$BUILD_DIR/temp"
ZIP_NAME="$PLUGIN_SLUG-v$VERSION.zip"

echo -e "${GREEN}======================================${NC}"
echo -e "${GREEN}BB Multisite Tools - Build Script${NC}"
echo -e "${GREEN}======================================${NC}"
echo ""
echo "Plugin: $PLUGIN_SLUG"
echo "Version: $VERSION"
echo ""

# Clean up previous builds
if [ -d "$BUILD_DIR" ]; then
    echo -e "${YELLOW}Cleaning up previous build...${NC}"
    rm -rf "$BUILD_DIR"
fi

# Create build directories
mkdir -p "$DIST_DIR"
mkdir -p "$TEMP_DIR/$PLUGIN_SLUG"

echo -e "${GREEN}Copying plugin files...${NC}"

# Copy only necessary files
cp "$PLUGIN_DIR/bb-multisite-tools.php" "$TEMP_DIR/$PLUGIN_SLUG/"
cp "$PLUGIN_DIR/polyfills.php" "$TEMP_DIR/$PLUGIN_SLUG/"
cp "$PLUGIN_DIR/admin.js" "$TEMP_DIR/$PLUGIN_SLUG/"
cp "$PLUGIN_DIR/style.css" "$TEMP_DIR/$PLUGIN_SLUG/"
cp "$PLUGIN_DIR/index.php" "$TEMP_DIR/$PLUGIN_SLUG/"

# Copy templates directory
cp -r "$PLUGIN_DIR/templates" "$TEMP_DIR/$PLUGIN_SLUG/"

# Copy documentation files
cp "$PLUGIN_DIR/README.md" "$TEMP_DIR/$PLUGIN_SLUG/"
cp "$PLUGIN_DIR/UPDATE-SYSTEM.md" "$TEMP_DIR/$PLUGIN_SLUG/"
cp "$PLUGIN_DIR/QUICK-START.md" "$TEMP_DIR/$PLUGIN_SLUG/"
cp "$PLUGIN_DIR/install-update-checker.sh" "$TEMP_DIR/$PLUGIN_SLUG/"
chmod +x "$TEMP_DIR/$PLUGIN_SLUG/install-update-checker.sh"

# Copy Plugin Update Checker if it exists
if [ -d "$PLUGIN_DIR/plugin-update-checker" ]; then
    echo -e "${GREEN}Including Plugin Update Checker library...${NC}"
    cp -r "$PLUGIN_DIR/plugin-update-checker" "$TEMP_DIR/$PLUGIN_SLUG/"
    # Clean up git files from update checker
    rm -rf "$TEMP_DIR/$PLUGIN_SLUG/plugin-update-checker/.git"
else
    echo -e "${YELLOW}Warning: plugin-update-checker not found. Users will need to run install-update-checker.sh${NC}"
fi

# Remove any .DS_Store files
find "$TEMP_DIR" -name ".DS_Store" -delete

# Remove any hidden files
find "$TEMP_DIR" -name ".*" -type f -delete

echo -e "${GREEN}Creating zip archive...${NC}"

# Create the zip file
cd "$TEMP_DIR"
zip -r "$DIST_DIR/$ZIP_NAME" "$PLUGIN_SLUG" -q

cd "$PLUGIN_DIR"

# Clean up temp directory
rm -rf "$TEMP_DIR"

# Get file size
FILE_SIZE=$(ls -lh "$DIST_DIR/$ZIP_NAME" | awk '{print $5}')

echo ""
echo -e "${GREEN}======================================${NC}"
echo -e "${GREEN}Build Complete!${NC}"
echo -e "${GREEN}======================================${NC}"
echo ""
echo "Output: $DIST_DIR/$ZIP_NAME"
echo "Size: $FILE_SIZE"
echo ""
echo "Files included:"
echo "  ✓ Core plugin files (PHP, JS, CSS)"
echo "  ✓ Templates directory"
echo "  ✓ Documentation (README, guides)"
echo "  ✓ Installation script"
if [ -d "$PLUGIN_DIR/plugin-update-checker" ]; then
    echo "  ✓ Plugin Update Checker library"
else
    echo "  ⚠ Plugin Update Checker (NOT included - users must install)"
fi
echo ""
echo "Files excluded:"
echo "  ✗ Git files (.git, .gitignore, .gitattributes)"
echo "  ✗ Build directory and scripts"
echo "  ✗ Development files (.DS_Store, etc.)"
echo ""
echo -e "${GREEN}Ready to distribute!${NC}"
echo ""
