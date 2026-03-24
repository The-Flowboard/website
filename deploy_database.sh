#!/bin/bash

# =====================================================
# AI Assessment - Database Deployment Script
# =====================================================
# This script deploys the assessment database tables
# and populates them with the 20 AI opportunities
# =====================================================

echo "=========================================="
echo "AI Assessment - Database Deployment"
echo "=========================================="
echo ""

# Database credentials (from db_config.php)
DB_HOST="localhost"
DB_NAME="jmc_website"
DB_USER="jmc_user"
DB_PASS="Sphinx208!"

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Check if SQL files exist
SCHEMA_FILE="$SCRIPT_DIR/database/assessment_schema.sql"
DATA_FILE="$SCRIPT_DIR/database/opportunities_data.sql"

if [ ! -f "$SCHEMA_FILE" ]; then
    echo "❌ ERROR: Schema file not found: $SCHEMA_FILE"
    exit 1
fi

if [ ! -f "$DATA_FILE" ]; then
    echo "❌ ERROR: Data file not found: $DATA_FILE"
    exit 1
fi

echo "Found database files:"
echo "  ✓ Schema: $SCHEMA_FILE"
echo "  ✓ Data: $DATA_FILE"
echo ""

# Test database connection
echo "Testing database connection..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT 1;" > /dev/null 2>&1

if [ $? -ne 0 ]; then
    echo "❌ ERROR: Cannot connect to database"
    echo "   Host: $DB_HOST"
    echo "   Database: $DB_NAME"
    echo "   User: $DB_USER"
    echo ""
    echo "Please verify your database credentials in db_config.php"
    exit 1
fi

echo "✓ Database connection successful"
echo ""

# Check if tables already exist
echo "Checking for existing tables..."
EXISTING_TABLES=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e "
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = '$DB_NAME'
    AND table_name IN ('ai_opportunities', 'assessment_submissions', 'assessment_opportunity_scores');
")

if [ "$EXISTING_TABLES" -gt 0 ]; then
    echo "⚠️  WARNING: Found $EXISTING_TABLES existing assessment table(s)"
    echo ""
    read -p "Do you want to DROP existing tables and recreate them? (yes/no): " CONFIRM

    if [ "$CONFIRM" != "yes" ]; then
        echo ""
        echo "Deployment cancelled. No changes made to database."
        exit 0
    fi

    echo ""
    echo "Dropping existing tables..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<EOF
DROP TABLE IF EXISTS assessment_opportunity_scores;
DROP TABLE IF EXISTS assessment_submissions;
DROP TABLE IF EXISTS ai_opportunities;
EOF

    if [ $? -eq 0 ]; then
        echo "✓ Existing tables dropped"
    else
        echo "❌ ERROR: Failed to drop existing tables"
        exit 1
    fi
fi

echo ""
echo "=========================================="
echo "STEP 1: Creating Database Schema"
echo "=========================================="
echo ""

mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SCHEMA_FILE"

if [ $? -eq 0 ]; then
    echo "✓ Schema created successfully"

    # Verify tables were created
    TABLE_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e "
        SELECT COUNT(*) FROM information_schema.tables
        WHERE table_schema = '$DB_NAME'
        AND table_name IN ('ai_opportunities', 'assessment_submissions', 'assessment_opportunity_scores');
    ")

    echo "  Created $TABLE_COUNT tables:"
    echo "    - ai_opportunities"
    echo "    - assessment_submissions"
    echo "    - assessment_opportunity_scores"
else
    echo "❌ ERROR: Failed to create schema"
    exit 1
fi

echo ""
echo "=========================================="
echo "STEP 2: Loading AI Opportunities Data"
echo "=========================================="
echo ""

mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$DATA_FILE"

if [ $? -eq 0 ]; then
    echo "✓ Data loaded successfully"

    # Verify opportunities were loaded
    OPP_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e "
        SELECT COUNT(*) FROM ai_opportunities;
    ")

    echo "  Loaded $OPP_COUNT AI opportunities"

    if [ "$OPP_COUNT" -eq 20 ]; then
        echo "  ✓ All 20 opportunities loaded correctly"
    else
        echo "  ⚠️  WARNING: Expected 20 opportunities, found $OPP_COUNT"
    fi
else
    echo "❌ ERROR: Failed to load data"
    exit 1
fi

echo ""
echo "=========================================="
echo "STEP 3: Verification"
echo "=========================================="
echo ""

# Show table structures
echo "Verifying table structures..."

for table in ai_opportunities assessment_submissions assessment_opportunity_scores; do
    COLUMN_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e "
        SELECT COUNT(*) FROM information_schema.columns
        WHERE table_schema = '$DB_NAME' AND table_name = '$table';
    ")
    echo "  ✓ $table: $COLUMN_COUNT columns"
done

echo ""

# Show sample data
echo "Sample opportunity data:"
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
    SELECT opportunity_id, name, category, pain_question
    FROM ai_opportunities
    LIMIT 5;
"

echo ""
echo "=========================================="
echo "✅ DEPLOYMENT COMPLETE!"
echo "=========================================="
echo ""
echo "Database tables created and populated successfully."
echo ""
echo "Next steps:"
echo "  1. Upload all PHP/JS/CSS files to your web server"
echo "  2. Test the webhook using test_webhook.php"
echo "  3. Complete a test assessment"
echo ""
echo "For detailed deployment instructions, see:"
echo "  DEPLOYMENT_CHECKLIST.md"
echo ""
