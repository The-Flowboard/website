#!/bin/bash
# Permanent Deployment Fix - One-Time Setup Script
# This script fixes SSH authentication and file permissions permanently

echo "=========================================="
echo "JMC Website - Permanent Deployment Fix"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Server details
SERVER_HOST="167.114.97.221"
SERVER_USER="ubuntu"
SERVER_PATH="/var/www/html"
SSH_KEY="$HOME/.ssh/id_ed25519"

echo "Step 1: Testing current SSH connection..."
echo "----------------------------------------"
if ssh -i "$SSH_KEY" -o BatchMode=yes -o ConnectTimeout=5 "${SERVER_USER}@${SERVER_HOST}" exit 2>/dev/null; then
    echo -e "${GREEN}✓ SSH key authentication already working!${NC}"
    SSH_WORKING=true
else
    echo -e "${YELLOW}⚠ SSH key authentication not working. Will set up.${NC}"
    SSH_WORKING=false
fi

echo ""
echo "Step 2: Add SSH key to server (if needed)..."
echo "----------------------------------------"

if [ "$SSH_WORKING" = false ]; then
    echo "Your SSH public key:"
    cat "${SSH_KEY}.pub"
    echo ""
    echo -e "${YELLOW}ACTION REQUIRED:${NC}"
    echo "1. The script will now copy your SSH key to the server"
    echo "2. You'll need to enter the server password ONE LAST TIME"
    echo "3. After this, password-free deployment will work!"
    echo ""
    read -p "Press Enter to continue (or Ctrl+C to cancel)..."

    # Copy SSH key to server
    ssh-copy-id -i "$SSH_KEY" "${SERVER_USER}@${SERVER_HOST}"

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ SSH key copied successfully!${NC}"
    else
        echo -e "${RED}✗ Failed to copy SSH key. Please check server password.${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✓ Skipping - SSH key already configured${NC}"
fi

echo ""
echo "Step 3: Fix file permissions permanently on server..."
echo "----------------------------------------"

ssh -i "$SSH_KEY" "${SERVER_USER}@${SERVER_HOST}" << 'ENDSSH'
# Create permissions fix script on server
sudo tee /usr/local/bin/fix-jmc-permissions.sh > /dev/null << 'EOF'
#!/bin/bash
# JMC Website - Permanent Permissions Fix
# Run this after any file uploads

cd /var/www/html

# Set ownership to www-data (Apache/Nginx user)
sudo chown -R www-data:www-data .

# Set correct permissions
sudo find . -type f -exec chmod 644 {} \;  # Files: rw-r--r--
sudo find . -type d -exec chmod 755 {} \;  # Directories: rwxr-xr-x

# Writable directories (uploads, cache, etc.)
sudo chmod 775 images/blog 2>/dev/null || true
sudo chmod 775 vendor 2>/dev/null || true

# Add ubuntu user to www-data group for easier editing
sudo usermod -a -G www-data ubuntu

# Set group sticky bit on /var/www/html so new files inherit group
sudo chmod g+s /var/www/html

echo "✓ Permissions fixed!"
echo "  - Files: 644 (rw-r--r--)"
echo "  - Directories: 755 (rwxr-xr-x)"
echo "  - Owner: www-data:www-data"
echo "  - Ubuntu user added to www-data group"
EOF

# Make the script executable
sudo chmod +x /usr/local/bin/fix-jmc-permissions.sh

# Run it now
echo "Running permissions fix..."
/usr/local/bin/fix-jmc-permissions.sh

# Add alias to ubuntu user's bashrc for easy access
if ! grep -q "alias fix-perms" ~/.bashrc; then
    echo "alias fix-perms='/usr/local/bin/fix-jmc-permissions.sh'" >> ~/.bashrc
    echo "✓ Added 'fix-perms' alias to ~/.bashrc"
fi

echo ""
echo "=========================================="
echo "Server-side setup complete!"
echo "=========================================="
ENDSSH

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Server permissions fixed!${NC}"
else
    echo -e "${RED}✗ Failed to fix server permissions${NC}"
    exit 1
fi

echo ""
echo "Step 4: Update VS Code SFTP config for auto-upload..."
echo "----------------------------------------"

# Backup existing config
if [ -f .vscode/sftp.json ]; then
    cp .vscode/sftp.json .vscode/sftp.json.backup.$(date +%Y%m%d_%H%M%S)
    echo "✓ Backed up existing sftp.json"
fi

# Create VS Code directory if it doesn't exist
mkdir -p .vscode

# Create new SFTP config with SSH key authentication
cat > .vscode/sftp.json << EOF
{
    "name": "JMC Production Server",
    "host": "${SERVER_HOST}",
    "protocol": "sftp",
    "port": 22,
    "username": "${SERVER_USER}",
    "privateKeyPath": "${SSH_KEY}",
    "remotePath": "${SERVER_PATH}",
    "uploadOnSave": true,
    "useTempFile": false,
    "openSsh": true,
    "ignore": [
        ".vscode",
        ".git",
        ".DS_Store",
        "node_modules",
        "*.md",
        "*.sh",
        ".env.example",
        "vendor",
        "composer.lock",
        ".github",
        "docs",
        "database",
        "n8n",
        "*.backup*",
        "ARCHIVE_*"
    ],
    "watcher": {
        "files": "**/*.{html,css,js,php,json}",
        "autoUpload": true,
        "autoDelete": false
    },
    "syncOption": {
        "delete": false,
        "skipCreate": false,
        "ignoreExisting": false,
        "update": true
    }
}
EOF

echo -e "${GREEN}✓ VS Code SFTP config updated!${NC}"

# Add .vscode/sftp.json to .gitignore if not already there
if ! grep -q ".vscode/sftp.json" .gitignore 2>/dev/null; then
    echo "" >> .gitignore
    echo "# VS Code SFTP config (contains sensitive paths)" >> .gitignore
    echo ".vscode/sftp.json" >> .gitignore
    echo "✓ Added .vscode/sftp.json to .gitignore"
fi

echo ""
echo "Step 5: Create quick deployment helper script..."
echo "----------------------------------------"

cat > quick-deploy.sh << 'EOF'
#!/bin/bash
# Quick Deploy Script - Deploy specific files or all changes

SERVER_USER="ubuntu"
SERVER_HOST="167.114.97.221"
SERVER_PATH="/var/www/html"
SSH_KEY="$HOME/.ssh/id_ed25519"

deploy_file() {
    local file=$1
    echo "Uploading: $file"
    scp -i "$SSH_KEY" "$file" "${SERVER_USER}@${SERVER_HOST}:${SERVER_PATH}/$file"
}

fix_permissions() {
    echo "Fixing permissions on server..."
    ssh -i "$SSH_KEY" "${SERVER_USER}@${SERVER_HOST}" "sudo /usr/local/bin/fix-jmc-permissions.sh"
}

case "$1" in
    "all")
        echo "Deploying all HTML and CSS files..."
        rsync -avz -e "ssh -i $SSH_KEY" \
            --include='*.html' \
            --include='*.css' \
            --include='*.js' \
            --include='*.php' \
            --include='css/***' \
            --include='js/***' \
            --include='php/***' \
            --include='admin/***' \
            --exclude='*' \
            ./ "${SERVER_USER}@${SERVER_HOST}:${SERVER_PATH}/"
        fix_permissions
        ;;
    "fix-perms")
        fix_permissions
        ;;
    "")
        echo "Usage: ./quick-deploy.sh [file|all|fix-perms]"
        echo ""
        echo "Examples:"
        echo "  ./quick-deploy.sh index.html          # Deploy single file"
        echo "  ./quick-deploy.sh css/main-styles.css # Deploy CSS file"
        echo "  ./quick-deploy.sh all                 # Deploy all files"
        echo "  ./quick-deploy.sh fix-perms           # Fix permissions only"
        ;;
    *)
        deploy_file "$1"
        fix_permissions
        ;;
esac
EOF

chmod +x quick-deploy.sh
echo -e "${GREEN}✓ Created quick-deploy.sh${NC}"

echo ""
echo "=========================================="
echo "✅ PERMANENT FIX COMPLETE!"
echo "=========================================="
echo ""
echo -e "${GREEN}Auto-upload is now enabled!${NC}"
echo ""
echo "What this fixed:"
echo "  ✓ SSH key authentication (no more passwords!)"
echo "  ✓ File permissions on server (www-data ownership)"
echo "  ✓ VS Code auto-upload on save"
echo "  ✓ Quick deployment script"
echo ""
echo "How to use:"
echo ""
echo "1. ${GREEN}Auto-upload (Recommended):${NC}"
echo "   - Open any file in VS Code"
echo "   - Make changes and save (Cmd+S / Ctrl+S)"
echo "   - File automatically uploads to server!"
echo ""
echo "2. ${GREEN}Manual deployment:${NC}"
echo "   ./quick-deploy.sh index.html         # Deploy single file"
echo "   ./quick-deploy.sh all                # Deploy everything"
echo ""
echo "3. ${GREEN}Fix permissions on server:${NC}"
echo "   ./quick-deploy.sh fix-perms          # Run permissions fix"
echo "   OR ssh to server and run: fix-perms  # Alias is set up!"
echo ""
echo "4. ${GREEN}GitHub Actions CI/CD:${NC}"
echo "   git push                             # Triggers automated deployment"
echo ""
echo "=========================================="
echo "Test it now:"
echo "  1. Open index.html in VS Code"
echo "  2. Make a small change (add a comment)"
echo "  3. Save the file (Cmd+S)"
echo "  4. Check VS Code Output panel (View → Output → SFTP)"
echo "  5. You should see: Upload success!"
echo "=========================================="
