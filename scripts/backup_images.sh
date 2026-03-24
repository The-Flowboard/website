#!/bin/bash

##############################################################################
# JMC Website Images Backup Script
#
# Purpose: Create automated backups of uploaded blog images
# Schedule: Run weekly on Sunday at 3 AM: 0 3 * * 0 /var/www/html/scripts/backup_images.sh
# Retention: 90 days
#
# Requirements:
#   - Write permissions to /var/backups/jmc_website
#   - tar command
#
# Optional:
#   - AWS CLI for S3 backups
##############################################################################

# Exit on any error
set -e

# Configuration
SOURCE_DIR="/var/www/html/images"
BACKUP_DIR="/var/backups/jmc_website/images"
DATE=$(date +%Y%m%d)
BACKUP_FILE="images_${DATE}.tar.gz"
RETENTION_DAYS=90

# Log file
LOG_FILE="/var/log/jmc_backup.log"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Create backup directory if it doesn't exist
if [ ! -d "$BACKUP_DIR" ]; then
    log_message "Creating backup directory: $BACKUP_DIR"
    mkdir -p "$BACKUP_DIR"
fi

# Verify source directory exists
if [ ! -d "$SOURCE_DIR" ]; then
    log_message "ERROR: Source directory not found: $SOURCE_DIR"
    exit 1
fi

# Start backup
log_message "Starting images backup..."
log_message "Source: $SOURCE_DIR"
log_message "Backup file: $BACKUP_FILE"

# Count files to backup
FILE_COUNT=$(find "$SOURCE_DIR" -type f | wc -l)
log_message "Files to backup: $FILE_COUNT"

# Perform tar backup with compression
if tar -czf "$BACKUP_DIR/$BACKUP_FILE" -C /var/www/html images/; then

    # Get file size
    FILE_SIZE=$(du -h "$BACKUP_DIR/$BACKUP_FILE" | cut -f1)
    log_message "Backup completed successfully: $BACKUP_FILE ($FILE_SIZE)"

    # Verify backup integrity
    if tar -tzf "$BACKUP_DIR/$BACKUP_FILE" > /dev/null 2>&1; then
        log_message "Backup integrity verified (tar test passed)"
    else
        log_message "WARNING: Backup integrity check failed"
    fi

else
    log_message "ERROR: Images backup failed"
    exit 1
fi

# Delete old backups (older than retention period)
log_message "Cleaning up old backups (retention: $RETENTION_DAYS days)..."
DELETED_COUNT=$(find "$BACKUP_DIR" -name "images_*.tar.gz" -mtime +$RETENTION_DAYS -delete -print | wc -l)
log_message "Deleted $DELETED_COUNT old backup(s)"

# List current backups
BACKUP_COUNT=$(ls -1 "$BACKUP_DIR"/images_*.tar.gz 2>/dev/null | wc -l)
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
log_message "Total backups: $BACKUP_COUNT (Total size: $TOTAL_SIZE)"

# Optional: Upload to S3 (uncomment if AWS CLI is configured)
# if command -v aws &> /dev/null; then
#     log_message "Uploading to S3..."
#     if aws s3 cp "$BACKUP_DIR/$BACKUP_FILE" "s3://jmc-backups/images/" --storage-class STANDARD_IA; then
#         log_message "S3 upload completed"
#     else
#         log_message "WARNING: S3 upload failed"
#     fi
# fi

log_message "Images backup process completed"
log_message "=========================================="

exit 0
