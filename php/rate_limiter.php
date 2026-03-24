<?php
/**
 * Simple file-based rate limiter
 * For production use with high traffic, consider using Redis or APCu
 */
class RateLimiter {
    private $storage_dir;

    public function __construct($storage_dir = null) {
        // Use system temp directory by default
        $this->storage_dir = $storage_dir ?? sys_get_temp_dir() . '/jmc_rate_limiter';

        if (!is_dir($this->storage_dir)) {
            mkdir($this->storage_dir, 0755, true);
        }
    }

    /**
     * Check if rate limit is exceeded
     * @param string $key Unique identifier (e.g., IP address or user ID)
     * @param int $max_attempts Maximum attempts allowed
     * @param int $window_seconds Time window in seconds
     * @return bool True if rate limit exceeded
     */
    public function isRateLimited($key, $max_attempts = 5, $window_seconds = 3600) {
        $file = $this->storage_dir . '/' . md5($key) . '.json';

        // Read existing attempts
        $attempts = [];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            $attempts = $data['attempts'] ?? [];
        }

        // Remove old attempts outside window
        $now = time();
        $attempts = array_filter($attempts, function($timestamp) use ($now, $window_seconds) {
            return ($now - $timestamp) < $window_seconds;
        });

        // Check if limit exceeded
        if (count($attempts) >= $max_attempts) {
            return true;
        }

        // Record this attempt
        $attempts[] = $now;
        file_put_contents($file, json_encode(['attempts' => $attempts]));

        return false;
    }

    /**
     * Get remaining time until rate limit resets
     * @param string $key Unique identifier
     * @param int $window_seconds Time window in seconds
     * @return int Seconds until reset
     */
    public function getResetTime($key, $window_seconds = 3600) {
        $file = $this->storage_dir . '/' . md5($key) . '.json';

        if (!file_exists($file)) {
            return 0;
        }

        $data = json_decode(file_get_contents($file), true);
        $attempts = $data['attempts'] ?? [];

        if (empty($attempts)) {
            return 0;
        }

        $oldest_attempt = min($attempts);
        $reset_time = ($oldest_attempt + $window_seconds) - time();

        return max(0, $reset_time);
    }

    /**
     * Clear rate limit for a specific key (useful for testing)
     * @param string $key Unique identifier
     */
    public function clearLimit($key) {
        $file = $this->storage_dir . '/' . md5($key) . '.json';
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Clean up old rate limit files (should be run periodically via cron)
     * @param int $older_than_seconds Delete files older than this many seconds
     */
    public function cleanup($older_than_seconds = 86400) {
        $now = time();
        $files = glob($this->storage_dir . '/*.json');

        foreach ($files as $file) {
            if (($now - filemtime($file)) > $older_than_seconds) {
                unlink($file);
            }
        }
    }
}
?>
