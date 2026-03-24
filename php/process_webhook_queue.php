<?php
/**
 * JMC Website - Webhook Queue Processor
 * Cron script to process pending webhooks
 *
 * Purpose: Background processor for webhook queue
 * Runs: Every 5 minutes via cron
 * Command: * /5 * * * * php /var/www/html/php/process_webhook_queue.php >> /var/log/jmc_webhooks.log 2>&1
 *
 * @author JMC Development Team
 * @created January 30, 2026
 * @version 1.0
 */

// Start time for performance tracking
$start_time = microtime(true);

// Load dependencies
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/webhook_queue.php';

/**
 * Log message with timestamp
 */
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] {$message}\n";
}

/**
 * Log error to file
 */
function logError($message) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[{$timestamp}] ERROR: {$message}");
    echo "[{$timestamp}] ERROR: {$message}\n";
}

// ===== MAIN PROCESSING =====
logMessage("=== Webhook Queue Processor Started ===");

try {
    // Initialize webhook queue
    $queue = new WebhookQueue($conn);

    // Process up to 50 pending webhooks
    logMessage("Processing pending webhooks...");
    $stats = $queue->processPending(50);

    // Log statistics
    logMessage("Processing complete:");
    logMessage("  - Processed: {$stats['processed']}");
    logMessage("  - Succeeded: {$stats['succeeded']}");
    logMessage("  - Failed: {$stats['failed']}");
    logMessage("  - Retried: {$stats['retried']}");

    // Get queue statistics
    logMessage("Current queue status:");
    $queue_stats = $queue->getStatistics();

    foreach ($queue_stats as $status => $data) {
        logMessage("  - {$status}: {$data['count']} webhooks (avg {$data['avg_retries']} retries)");
    }

    // Show recent failures if any
    if (isset($queue_stats['failed']) && $queue_stats['failed']['count'] > 0) {
        logMessage("Recent failures:");
        $failures = $queue->getRecentFailures(5);

        foreach ($failures as $failure) {
            $event = $failure['event'];
            $retry_count = $failure['retry_count'];
            $error = substr($failure['error_message'], 0, 100);
            logMessage("  - Webhook #{$failure['id']} ({$event}) - {$retry_count} retries - Error: {$error}");
        }
    }

    // Cleanup old records (run once per day at midnight)
    $current_hour = (int)date('H');
    $current_minute = (int)date('i');

    if ($current_hour === 0 && $current_minute < 5) {
        logMessage("Running daily cleanup...");
        $completed_deleted = $queue->cleanupCompleted(30);
        $failed_deleted = $queue->cleanupFailed(90);
        logMessage("  - Deleted {$completed_deleted} old completed webhooks");
        logMessage("  - Deleted {$failed_deleted} old failed webhooks");
    }

    // Performance tracking
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2);
    logMessage("Execution time: {$execution_time}ms");

    logMessage("=== Webhook Queue Processor Finished ===\n");

    // Close database connection
    $conn->close();

    // Exit with success
    exit(0);

} catch (Exception $e) {
    // Handle fatal errors
    logError("Fatal error: " . $e->getMessage());
    logError("Stack trace: " . $e->getTraceAsString());

    // Close database connection if open
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }

    // Exit with error
    exit(1);
}

?>
