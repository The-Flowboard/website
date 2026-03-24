#!/bin/bash

##############################################################################
# JMC Website Database Backup Script
#
# Purpose: Create automated backups of the jmc_website MySQL database
# Schedule: Run daily at 2 AM via cron: 0 2 * * * /var/www/html/scripts/backup_database.sh
# Retention: 30 days
#
# Requirements:
#   - .env file with database credentials in /var/www/html
#   - Write permissions to /var/backups/jmc_website
#   - mysqldump command
#
# Optional:
#   - AWS CLI for S3 backups
##############################################################################

# Exit on any error
set -e

# Determine script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$( cd "$SCRIPT_DIR/.." && pwd )"

# Load environment variables from .env file
if [ -f "$PROJECT_ROOT/.env" ]; then
    export $(grep -v '^#' "$PROJECT_ROOT/.env" | xargs)
else
    echo "ERROR: .env file not found at $PROJECT_ROOT/.env"
    exit 1
fi

# Configuration
DB_USER="${DB_USER}"
DB_PASS="${DB_PASS}"
DB_NAME="${DB_NAME}"
DB_HOST="${DB_HOST:-localhost}"
BACKUP_DIR="/var/backups/jmc_website/database"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="db_${DATE}.sql.gz"
RETENTION_DAYS=30

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

# Verify mysqldump is available
if ! command -v mysqldump &> /dev/null; then
    log_message "ERROR: mysqldump command not found"
    exit 1
fi

# Start backup
log_message "Starting database backup..."
log_message "Database: $DB_NAME"
log_message "Backup file: $BACKUP_FILE"

# Perform database dump with compression
if mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --set-gtid-purged=OFF \
    "$DB_NAME" | gzip > "$BACKUP_DIR/$BACKUP_FILE"; then

    # Get file size
    FILE_SIZE=$(du -h "$BACKUP_DIR/$BACKUP_FILE" | cut -f1)
    log_message "Backup completed successfully: $BACKUP_FILE ($FILE_SIZE)"

    # Verify backup integrity
    if gunzip -t "$BACKUP_DIR/$BACKUP_FILE" 2>/dev/null; then
        log_message "Backup integrity verified (gzip test passed)"
    else
        log_message "WARNING: Backup integrity check failed"
    fi

else
    log_message "ERROR: Database backup failed"
    exit 1
fi

# Delete old backups (older than retention period)
log_message "Cleaning up old backups (retention: $RETENTION_DAYS days)..."
DELETED_COUNT=$(find "$BACKUP_DIR" -name "db_*.sql.gz" -mtime +$RETENTION_DAYS -delete -print | wc -l)
log_message "Deleted $DELETED_COUNT old backup(s)"

# List current backups
BACKUP_COUNT=$(ls -1 "$BACKUP_DIR"/db_*.sql.gz 2>/dev/null | wc -l)
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
log_message "Total backups: $BACKUP_COUNT (Total size: $TOTAL_SIZE)"

# Optional: Upload to S3 (uncomment if AWS CLI is configured)
# if command -v aws &> /dev/null; then
#     log_message "Uploading to S3..."
#     if aws s3 cp "$BACKUP_DIR/$BACKUP_FILE" "s3://jmc-backups/database/" --storage-class STANDARD_IA; then
#         log_message "S3 upload completed"
#     else
#         log_message "WARNING: S3 upload failed"
#     fi
# fi

log_message "Backup process completed"
log_message "=========================================="

exit 0
