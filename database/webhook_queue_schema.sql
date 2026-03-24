-- =====================================================
-- JMC WEBSITE - WEBHOOK QUEUE SYSTEM
-- Database Schema for Reliable Webhook Delivery
-- =====================================================
-- Created: January 30, 2026
-- Purpose: Queue webhooks with retry logic for n8n integration
-- Dependencies: MySQL 5.7+ / MariaDB 10.2+
-- =====================================================

-- Drop table if exists (for clean reinstall)
-- CAUTION: This will delete all queued webhooks!
-- DROP TABLE IF EXISTS webhook_queue;

-- =====================================================
-- WEBHOOK QUEUE TABLE
-- =====================================================
-- Stores webhooks for asynchronous processing with retry logic
-- Ensures zero data loss if n8n is temporarily unavailable

CREATE TABLE IF NOT EXISTS webhook_queue (
    -- Primary key
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Webhook identification
    event VARCHAR(100) NOT NULL COMMENT 'Event type: contact_form, assessment, etc.',

    -- Webhook data
    payload JSON NOT NULL COMMENT 'Complete webhook payload as JSON',

    -- Status tracking
    status ENUM('pending', 'processing', 'completed', 'failed')
        DEFAULT 'pending'
        COMMENT 'Current status of webhook delivery',

    -- Retry logic
    retry_count INT DEFAULT 0 COMMENT 'Number of delivery attempts made',
    max_retries INT DEFAULT 5 COMMENT 'Maximum retries before marking as failed',
    next_retry_at TIMESTAMP NULL COMMENT 'When to retry next (exponential backoff)',

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When webhook was queued',
    processed_at TIMESTAMP NULL COMMENT 'When webhook was successfully delivered',

    -- Error tracking
    error_message TEXT NULL COMMENT 'Last error message if delivery failed',

    -- Indexes for performance
    INDEX idx_status (status),
    INDEX idx_next_retry (next_retry_at),
    INDEX idx_event (event),
    INDEX idx_created_at (created_at)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Queue for reliable webhook delivery with retry logic';

-- =====================================================
-- SAMPLE DATA (for testing)
-- =====================================================
-- Uncomment to insert test webhook

-- INSERT INTO webhook_queue (event, payload, max_retries) VALUES
-- (
--     'contact_form',
--     JSON_OBJECT(
--         'id', 999,
--         'first_name', 'Test',
--         'last_name', 'User',
--         'email', 'test@example.com',
--         'phone', '555-0100',
--         'company', 'Test Company',
--         'message', 'This is a test webhook',
--         'referral_source', 'Other'
--     ),
--     3
-- );

-- =====================================================
-- USAGE QUERIES
-- =====================================================

-- View pending webhooks
-- SELECT * FROM webhook_queue WHERE status = 'pending' ORDER BY created_at DESC LIMIT 10;

-- View failed webhooks
-- SELECT * FROM webhook_queue WHERE status = 'failed' ORDER BY created_at DESC LIMIT 10;

-- View retry statistics
-- SELECT
--     event,
--     status,
--     COUNT(*) as count,
--     AVG(retry_count) as avg_retries,
--     MAX(retry_count) as max_retries
-- FROM webhook_queue
-- GROUP BY event, status;

-- Reset failed webhook for retry (be careful!)
-- UPDATE webhook_queue
-- SET status = 'pending', retry_count = 0, next_retry_at = NULL, error_message = NULL
-- WHERE id = [WEBHOOK_ID];

-- Clean up old completed webhooks (older than 30 days)
-- DELETE FROM webhook_queue
-- WHERE status = 'completed'
-- AND processed_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Clean up old failed webhooks (older than 90 days)
-- DELETE FROM webhook_queue
-- WHERE status = 'failed'
-- AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- =====================================================
-- DEPLOYMENT INSTRUCTIONS
-- =====================================================
--
-- 1. Deploy to production:
--    mysql -u jmc_user -p'Sphinx208!' jmc_website < database/webhook_queue_schema.sql
--
-- 2. Verify table created:
--    mysql -u jmc_user -p'Sphinx208!' jmc_website -e "DESCRIBE webhook_queue;"
--
-- 3. Set up cron job for processing queue:
--    */5 * * * * php /var/www/html/php/process_webhook_queue.php >> /var/log/jmc_webhooks.log 2>&1
--
-- 4. Monitor queue:
--    mysql -u jmc_user -p'Sphinx208!' jmc_website -e "SELECT status, COUNT(*) FROM webhook_queue GROUP BY status;"
--
-- =====================================================
-- MAINTENANCE
-- =====================================================
--
-- Set up automated cleanup (add to monthly cron):
-- 0 3 1 * * mysql -u jmc_user -p'Sphinx208!' jmc_website -e "DELETE FROM webhook_queue WHERE status='completed' AND processed_at < DATE_SUB(NOW(), INTERVAL 30 DAY);"
--
-- =====================================================
-- END OF SCHEMA
-- =====================================================
