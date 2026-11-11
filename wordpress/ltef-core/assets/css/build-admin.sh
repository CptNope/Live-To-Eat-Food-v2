#!/bin/sh
# Builds admin-colors.css from src/admin-colors.scss with Dart Sass.
# WP_CORE is a WordPress install (its wp-admin/css/colors holds the scheme template).
set -e
cd "$(dirname "$0")"
WP_CORE=${WP_CORE:-/tmp/wp}
mkdir -p "$WP_CORE/wp-admin/css/colors/ltef"
cp src/admin-colors.scss "$WP_CORE/wp-admin/css/colors/ltef/colors.scss"
sass --no-source-map --style=expanded --silence-deprecation=import "$WP_CORE/wp-admin/css/colors/ltef/colors.scss" admin-colors.css
echo "wrote admin-colors.css"
