#!/bin/sh

# Setup Git hooks for the project

# Get the absolute path to the current directory (where this script is located)
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
HOOKS_DIR="$PROJECT_ROOT/.git/hooks"
PROJECT_HOOKS_DIR="$SCRIPT_DIR/hooks"

echo "Setting up Git hooks..."
echo "Project root: $PROJECT_ROOT"
echo "Hooks directory: $HOOKS_DIR"
echo "Project hooks directory: $PROJECT_HOOKS_DIR"

# Change directory to project root
cd "$HOOKS_DIR" || {
  echo "Error: Cannot change directory to project root: $HOOKS_DIR"
  exit 1
}
echo "Pulling the php-cs-fixer image"
docker pull ghcr.io/php-cs-fixer/php-cs-fixer:3-php8.4 || {
	echo "Error: php-cs-fixer:3-php8.4 image"
    exit 1
}

# Create symlink using relative paths from project root
ln -sf "../../deploy/git/hooks/pre-commit.sh" "pre-commit" || {
  echo "Error: Failed to create symlink for pre-commit hook"
  exit 1
}
# Make hooks executable
chmod +x "$PROJECT_HOOKS_DIR"/*.sh 2>/dev/null || true

echo "Git hooks setup complete!"
