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

# Check all the .php files and update the version number.
echo "➤ Updating version number..."
find ./src -type f -name '*.php' -exec sed -i '' -E "s/(@version)[[:space:]]+[0-9.]+/\1 $VERSION/" {} \;
echo "✓ Version number updated!"

# Check phpcs coding standards.
echo "➤ Checking coding standards..."
composer install
# run phpcs if failed, exit with error
if ! composer run phpcs; then
	echo "✘ Coding standards check failed. Please fix the errors and try again."
	exit 1
fi
echo "✓ Coding standards check passed!"

# Push the changes to the repository and create release.
echo "➤ Pushing changes and creating GitHub release..."
git add .
git commit -m "Release v$VERSION"
git tag -a "v$VERSION" -m "Release v$VERSION"
git push origin master --tags
echo "✓ Changes pushed and GitHub release created!"
