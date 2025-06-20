#!/bin/sh
# Exit if any command fails.
set -e

# Show the last tag by sorting the tags.
echo "➤ Fetching the latest tags..."
git fetch --tags
echo "✓ Latest tags fetched!"

# Get and show the last tag
LAST_TAG=$(git describe --tags --abbrev=0 2>/dev/null || echo "No tags found")
echo "➤ Last release tag: $LAST_TAG"

# Ask the user for the version number
read -p "Enter the version number to release (e.g. 1.0.0): " VERSION

# Show the release version and ask for confirmation.
echo "➤ Preparing release for $VERSION..."
read -p "Are you sure you want to release version $VERSION? (y/n) " -n 1 -r
echo ""

# Update version number in PHP files.
echo "➤ Updating version number..."
find ./src -type f -name '*.php' -exec sed -i '' -E "s/(@version)[[:space:]]+[0-9.]+/\1 $VERSION/" {} \;
echo "✓ Version number updated!"

# Check phpcs coding standards.
echo "➤ Checking coding standards..."
composer install
if ! composer run phpcs; then
	echo "✘ Coding standards check failed. Please fix the errors and try again."
	exit 1
fi
echo "✓ Coding standards check passed!"

# Check if tag already exists and delete if it does
if git rev-parse "v$VERSION" >/dev/null 2>&1; then
    echo "⚠ Tag v$VERSION already exists. Deleting existing tag..."
    git tag -d "v$VERSION"
    git push origin ":refs/tags/v$VERSION"
    echo "✓ Existing tag v$VERSION deleted."
fi

# Commit, tag, and push
echo "➤ Committing changes and creating Git tag..."
git add .
git commit -m "Release v$VERSION"
git tag -a "v$VERSION" -m "Release v$VERSION"
git push origin master
git push origin "v$VERSION"
echo "✓ Changes pushed!"

# Check if gh CLI is installed
if command -v gh >/dev/null 2>&1; then
    echo "➤ Creating GitHub release..."
    gh release create "v$VERSION" --title "Release v$VERSION" --notes "Release v$VERSION"
    echo "✓ GitHub release created!"
else
    echo "⚠ 'gh' CLI not found. Skipping GitHub release creation."
fi
