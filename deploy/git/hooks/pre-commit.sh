#!/bin/sh
set -e

# Resolve absolute directory of this script (pre-commit.sh)
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Project root is three levels up from this script location (deploy/git/hooks)
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$PROJECT_ROOT" || {
  echo "Error: Cannot change directory to project root: $PROJECT_ROOT"
  exit 1
}

API_DIR="api"
PHP_CS_FIXER="docker run --rm -v $(pwd)/api:/code ghcr.io/php-cs-fixer/php-cs-fixer:3-php8.4"

# Get list of staged PHP files under api/ directory
STAGED_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '^api/.*\.php$' | sed 's#^api/##' || true)

if [ -z "$STAGED_FILES" ]; then
  echo "No staged PHP files inside api/ directory."
  exit 0
fi

echo "Running php-cs-fixer on staged PHP files:"
echo "$STAGED_FILES"

# Run php-cs-fixer with config on all staged api/ PHP files
echo "$STAGED_FILES" | xargs $PHP_CS_FIXER fix --config=".php-cs-fixer.dist.php" --verbose --


# Stage any files modified by php-cs-fixer
MODIFIED_FILES=$(git diff --name-only)

if [ -n "$MODIFIED_FILES" ]; then
  echo "Staging files updated by php-cs-fixer:"
  echo "$MODIFIED_FILES"
  git add $MODIFIED_FILES
fi

echo "Pre-commit php-cs-fixer completed successfully."
exit 0
