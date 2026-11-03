#!/bin/sh
# Rebuild ltef.css from its parts: tokens + page layout, the design system's components, page sections, then WordPress blocks.
cd "$(dirname "$0")" && cat src/site.css src/components.css src/site-pages.css src/wp.css > ltef.css && echo "ltef.css: $(wc -c < ltef.css) bytes"
