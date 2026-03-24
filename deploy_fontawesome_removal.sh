#!/bin/bash
# Font Awesome Replacement Deployment Script
# Removes Font Awesome CDN (75KB gzipped) and replaces with inline SVG

echo "========================================"
echo "Font Awesome Removal Deployment"
echo "========================================"
echo ""

# Files to upload
FILES=(
  "index.html"
  "about.html"
  "services.html"
  "blog.html"
  "courses.html"
  "contact.html"
  "assessment.html"
  "privacy-policy.html"
  "terms-of-service.html"
  "404.html"
  "css/main-styles.css"
)

echo "Files to deploy:"
for file in "${FILES[@]}"; do
  echo "  - $file"
done
echo ""

# Upload files via SFTP
echo "Uploading files to production server..."
for file in "${FILES[@]}"; do
  echo "Uploading: $file"
  scp "$file" ubuntu@167.114.97.221:/var/www/html/$file
done

# Set permissions
echo ""
echo "Setting correct file permissions..."
ssh ubuntu@167.114.97.221 << 'ENDSSH'
cd /var/www/html

# Set ownership
sudo chown www-data:www-data index.html about.html services.html blog.html courses.html contact.html assessment.html privacy-policy.html terms-of-service.html 404.html css/main-styles.css

# Set permissions
sudo chmod 644 index.html about.html services.html blog.html courses.html contact.html assessment.html privacy-policy.html terms-of-service.html 404.html css/main-styles.css

echo "Permissions set successfully!"
ENDSSH

echo ""
echo "========================================"
echo "Deployment Complete!"
echo "========================================"
echo ""
echo "Changes deployed:"
echo "✅ Replaced Font Awesome fa-bars icon with inline SVG (index.html)"
echo "✅ Removed Font Awesome CDN link from all 10 HTML pages"
echo "✅ Added CSS styles for SVG icon (main-styles.css)"
echo ""
echo "Performance improvements:"
echo "  - Removed 75KB gzipped download"
echo "  - Removed 1 HTTP request"
echo "  - Faster page load"
echo ""
echo "Test the changes:"
echo "  1. Visit https://joshimc.com"
echo "  2. Click mobile menu button (resize browser to <1024px)"
echo "  3. Verify hamburger icon displays correctly"
echo ""
