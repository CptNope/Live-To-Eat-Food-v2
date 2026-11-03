#!/usr/bin/env bash
# Builds the two installable zips into wordpress/dist/ (not committed).
# Usage: bash wordpress/build-zips.sh
set -euo pipefail
cd "$(dirname "$0")"
bash live-to-eat-food/assets/css/build.sh
rm -rf dist && mkdir -p dist
zip -qr dist/live-to-eat-food.zip live-to-eat-food -x '*/.DS_Store'
zip -qr dist/ltef-core.zip ltef-core -x '*/.DS_Store'
ls -lh dist
