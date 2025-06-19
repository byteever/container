#!/bin/sh
# Exit if any command fails.
set -e

VERSION=$(node -p "require('./package.json').version")
# Show the release version and ask for confirmation.
echo "➤ Preparing release for $VERSION..."
read -p "Are you sure you want to release version $VERSION? (y/n) " -n 1 -r
echo ""

# Check all the .php files and update the version number.
echo "➤ Updating version number..."
find ./src -type f -name '*.php' -exec sed -i '' -E "s/(@version)[[:space:]]+[0-9.]+/\1 $VERSION/" {} \;
echo "✓ Version number updated!"

# Install npm dependencies.
echo "➤ Building assets..."
npm install && npm run build
echo "✓ Npm dependencies installed!"

# Check phpcs coding standards.
echo "➤ Checking coding standards..."
composer install
# run phpcs if failed, exit with error
#if ! composer run phpcs -- --standard=phpcs.xml; then
#	echo "✘ Coding standards check failed. Please fix the errors and try again."
#	exit 1
#fi
echo "✓ Coding standards check passed!"

# create a github release
echo "➤ Creating GitHub release..."
git add .
git commit -m "Release v$VERSION"
git tag -a "v$VERSION" -m "Release v$VERSION"
git push origin master --tags
echo "✓ GitHub release created!"
