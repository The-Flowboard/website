#!/bin/bash

# Script to replace inline CSS with external link in all HTML files

# CSS link replacement
CSS_LINK='    <!-- External CSS -->
    <link rel="stylesheet" href="/css/main-styles.css">'

# Process each HTML file
for file in index.html about.html services.html blog.html courses.html contact.html assessment.html privacy-policy.html terms-of-service.html; do
    echo "Processing $file..."

    # Create backup
    cp "$file" "$file.bak"

    # Find style tag lines
    START=$(grep -n '<style>' "$file" | head -1 | cut -d: -f1)
    END=$(grep -n '</style>' "$file" | head -1 | cut -d: -f1)

    if [ -n "$START" ] && [ -n "$END" ]; then
        # Replace inline CSS with external link
        sed -i.tmp "${START},${END}c\\
${CSS_LINK}
" "$file"

        rm "$file.tmp"
        echo "  ✓ Replaced inline CSS (lines $START-$END) with external link"
    else
        echo "  ⚠ No style tag found"
    fi
done

echo "Done! CSS extraction complete."
echo "New file created: css/main-styles.css"
echo "Backups created with .bak extension"
