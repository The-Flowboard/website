<?php
/**
 * JMC Website - Webhook Queue System
 * Reliable webhook delivery with automatic retry logic
 *
 * Purpose: Ensures zero data loss when n8n is temporarily unavailable
 * Features:
 * - Asynchronous webhook processing
 * - Exponential backoff retry logic
 * - Automatic failure handling
 * - Queue monitoring and statistics
 *
 * @author JMC Development Team
 * @created January 30, 2026
 * @version 1.0
 */

require_once __DIR__ . '/db_config.php';

class WebhookQueue {
    private $conn;
    private $webhook_urls;

    /**
     * Initialize webhook queue
     * @param mysqli $conn Database connection
     */
    public function __construct($conn) {
        $this->conn = $conn;

        // Webhook URLs for different event types
        $this->webhook_urls = [
            'contact_form' => 'https://n8n.joshimc.com/webhook/529e4b39-b4a7-491d-bfe8-3e7d2d0c7936',
            'assessment' => 'https://n8n.joshimc.com/webhook/2fa44a47-4368-4ec5-81c5-d30a2de72e92',
        ];
    }

    /**
     * Enqueue webhook for processing
     *
     * @param string $event Event type (contact_form, assessment)
     * @param array $payload Webhook data
     * @param int $max_retries Maximum retry attempts (default: 5)
     * @return bool|int Webhook ID on success, false on failure
     */
    public function enqueue($event, $payload, $max_retries = 5) {
        $payload_json = json_encode($payload);

        if ($payload_json === false) {
            error_log("Webhook Queue: Failed to encode payload for event '{$event}'");
            return false;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO webhook_queue (event, payload, max_retries)
             VALUES (?, ?, ?)"
        );

        if (!$stmt) {
            error_log("Webhook Queue: Prepare failed - " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("ssi", $event, $payload_json, $max_retries);

        if ($stmt->execute()) {
            $webhook_id = $this->conn->insert_id;
            error_log("Webhook Queue: Enqueued webhook #{$webhook_id} for event '{$event}'");
            return $webhook_id;
        } else {
            error_log("Webhook Queue: Insert failed - " . $stmt->error);
            return false;
        }
    }

    /**
     * Process pending webhooks from queue
     *
     * @param int $limit Maximum number of webhooks to process (default: 10)
     * @return array Statistics about processed webhooks
     */
    public function processPending($limit = 10) {
        $stats = [
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'retried' => 0
        ];

        // Select pending webhooks ready for processing
        $stmt = $this->conn->prepare(
            "SELECT id, event, payload, retry_count, max_retries
             FROM webhook_queue
             WHERE status = 'pending'
             AND retry_count < max_retries
             AND (next_retry_at IS NULL OR next_retry_at <= NOW())
             ORDER BY created_at ASC
             LIMIT ?"
        );

        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($webhook = $result->fetch_assoc()) {
            $stats['processed']++;

            $success = $this->processWebhook($webhook);

            if ($success) {
                $stats['succeeded']++;
            } else {
                if ($webhook['retry_count'] + 1 < $webhook['max_retries']) {
                    $stats['retried']++;
                } else {
                    $stats['failed']++;
                }
            }
        }

        return $stats;
    }

    /**
     * Process single webhook
     *
     * @param array $webhook Webhook data from database
     * @return bool True if successfully delivered, false otherwise
     */
    private function processWebhook($webhook) {
        $id = $webhook['id'];
        $event = $webhook['event'];
        $payload = json_decode($webhook['payload'], true);
        $retry_count = $webhook['retry_count'];

        // Mark as processing
        $this->updateStatus($id, 'processing');

        // Get webhook URL for this event type
        $webhook_url = $this->getWebhookUrl($event);

        if (!$webhook_url) {
            $error = "No webhook URL configured for event '{$event}'";
            error_log("Webhook Queue: {$error}");
            $this->updateStatus($id, 'failed', $error);
            return false;
        }

        try {
            // Send webhook via cURL
            $ch = curl_init($webhook_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'User-Agent: JMC-Webhook-Queue/1.0'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            // Check if delivery succeeded (2xx HTTP status code)
            if ($http_code >= 200 && $http_code < 300) {
                error_log("Webhook Queue: Successfully delivered webhook #{$id} (event: {$event}, HTTP {$http_code})");
                $this->updateStatus($id, 'completed');
                return true;
            } else {
                // Delivery failed
                $error = "HTTP {$http_code}: " . ($curl_error ?: $response);
                throw new Exception($error);
            }

        } catch (Exception $e) {
            // Handle failure with retry logic
            $retry_count++;
            $error_message = $e->getMessage();

            error_log("Webhook Queue: Failed to deliver webhook #{$id} (attempt {$retry_count}/{$webhook['max_retries']}): {$error_message}");

            if ($retry_count >= $webhook['max_retries']) {
                // Max retries exceeded - mark as permanently failed
                error_log("Webhook Queue: Max retries exceeded for webhook #{$id}, marking as failed");
                $this->updateStatus($id, 'failed', $error_message);
            } else {
                // Schedule retry with exponential backoff
                $next_retry = $this->calculateNextRetry($retry_count);
                $this->scheduleRetry($id, $retry_count, $next_retry, $error_message);
                error_log("Webhook Queue: Scheduled retry for webhook #{$id} at {$next_retry}");
            }

            return false;
        }
    }

    /**
     * Get webhook URL for event type
     *
     * @param string $event Event type
     * @return string|null Webhook URL or null if not found
     */
    private function getWebhookUrl($event) {
        return $this->webhook_urls[$event] ?? null;
    }

    /**
     * Calculate next retry time with exponential backoff
     *
     * Backoff schedule:
     * - Retry 1: 1 minute
     * - Retry 2: 2 minutes
     * - Retry 3: 4 minutes
     * - Retry 4: 8 minutes
     * - Retry 5: 16 minutes
     * - Max delay: 1 hour
     *
     * @param int $retry_count Number of retries attempted
     * @return string Next retry timestamp (Y-m-d H:i:s)
     */
    private function calculateNextRetry($retry_count) {
        // Exponential backoff: 2^retry_count minutes
        $delay_seconds = pow(2, $retry_count) * 60;

        // Cap at 1 hour maximum
        $max_delay = 3600;
        $delay = min($delay_seconds, $max_delay);

        return date('Y-m-d H:i:s', time() + $delay);
    }

    /**
     * Update webhook status
     *
     * @param int $id Webhook ID
     * @param string $status New status (pending, processing, completed, failed)
     * @param string|null $error Optional error message
     * @return bool Success status
     */
    private function updateStatus($id, $status, $error = null) {
        if ($status === 'completed' || $status === 'failed') {
            $stmt = $this->conn->prepare(
                "UPDATE webhook_queue
                 SET status = ?, processed_at = NOW(), error_message = ?
                 WHERE id = ?"
            );
            $stmt->bind_param("ssi", $status, $error, $id);
        } else {
            $stmt = $this->conn->prepare(
                "UPDATE webhook_queue
                 SET status = ?
                 WHERE id = ?"
            );
            $stmt->bind_param("si", $status, $id);
        }

        return $stmt->execute();
    }

    /**
     * Schedule webhook for retry
     *
     * @param int $id Webhook ID
     * @param int $retry_count Updated retry count
     * @param string $next_retry Next retry timestamp
     * @param string $error Error message from last attempt
     * @return bool Success status
     */
    private function scheduleRetry($id, $retry_count, $next_retry, $error) {
        $stmt = $this->conn->prepare(
            "UPDATE webhook_queue
             SET status = 'pending',
                 retry_count = ?,
                 next_retry_at = ?,
                 error_message = ?
             WHERE id = ?"
        );

        $stmt->bind_param("issi", $retry_count, $next_retry, $error, $id);

        return $stmt->execute();
    }

    /**
     * Get queue statistics
     *
     * @return array Queue statistics by status
     */
    public function getStatistics() {
        $stmt = $this->conn->prepare(
            "SELECT
                status,
                COUNT(*) as count,
                AVG(retry_count) as avg_retries,
                MAX(retry_count) as max_retries
             FROM webhook_queue
             GROUP BY status"
        );

        $stmt->execute();
        $result = $stmt->get_result();

        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[$row['status']] = [
                'count' => (int)$row['count'],
                'avg_retries' => round($row['avg_retries'], 2),
                'max_retries' => (int)$row['max_retries']
            ];
        }

        return $stats;
    }

    /**
     * Get recent failed webhooks for debugging
     *
     * @param int $limit Number of failures to retrieve
     * @return array Failed webhook records
     */
    public function getRecentFailures($limit = 10) {
        $stmt = $this->conn->prepare(
            "SELECT id, event, retry_count, error_message, created_at
             FROM webhook_queue
             WHERE status = 'failed'
             ORDER BY created_at DESC
             LIMIT ?"
        );

        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $failures = [];
        while ($row = $result->fetch_assoc()) {
            $failures[] = $row;
        }

        return $failures;
    }

    /**
     * Clean up old completed webhooks
     *
     * @param int $days Age in days (default: 30)
     * @return int Number of records deleted
     */
    public function cleanupCompleted($days = 30) {
        $stmt = $this->conn->prepare(
            "DELETE FROM webhook_queue
             WHERE status = 'completed'
             AND processed_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );

        $stmt->bind_param("i", $days);
        $stmt->execute();

        $deleted = $stmt->affected_rows;
        error_log("Webhook Queue: Cleaned up {$deleted} completed webhooks older than {$days} days");

        return $deleted;
    }

    /**
     * Clean up old failed webhooks
     *
     * @param int $days Age in days (default: 90)
     * @return int Number of records deleted
     */
    public function cleanupFailed($days = 90) {
        $stmt = $this->conn->prepare(
            "DELETE FROM webhook_queue
             WHERE status = 'failed'
             AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );

        $stmt->bind_param("i", $days);
        $stmt->execute();

        $deleted = $stmt->affected_rows;
        error_log("Webhook Queue: Cleaned up {$deleted} failed webhooks older than {$days} days");

        return $deleted;
    }
}

?>
