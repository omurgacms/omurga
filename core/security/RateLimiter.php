<?php
/**
 * Rate Limiter
 * 
 * Implements rate limiting to prevent abuse of API endpoints and forms.
 */

namespace Omurga\Security;

class RateLimiter
{
    /**
     * @var array In-memory storage for rate limit data
     */
    private static $limits = [];
    
    /**
     * @var int Default time window in seconds
     */
    private static $defaultWindow = 3600;
    
    /**
     * Check if action is rate limited
     * 
     * @param string $key Unique identifier (e.g., IP address, user ID)
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $windowSeconds Time window in seconds
     * @return array ['allowed' => bool, 'remaining' => int, 'resetAt' => int]
     */
    public static function check(string $key, int $maxAttempts, int $windowSeconds = null): array
    {
        $windowSeconds = $windowSeconds ?? self::$defaultWindow;
        $now = time();
        
        // Initialize if not exists
        if (!isset(self::$limits[$key])) {
            self::$limits[$key] = [
                'attempts' => 0,
                'resetAt' => $now + $windowSeconds,
            ];
        }
        
        $limit = self::$limits[$key];
        
        // Reset if window has expired
        if ($now >= $limit['resetAt']) {
            $limit = self::$limits[$key] = [
                'attempts' => 0,
                'resetAt' => $now + $windowSeconds,
            ];
        }
        
        $allowed = $limit['attempts'] < $maxAttempts;
        $remaining = max(0, $maxAttempts - $limit['attempts']);
        
        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'resetAt' => $limit['resetAt'],
        ];
    }
    
    /**
     * Record an attempt
     * 
     * @param string $key
     */
    public static function recordAttempt(string $key): void
    {
        if (!isset(self::$limits[$key])) {
            self::$limits[$key] = [
                'attempts' => 0,
                'resetAt' => time() + self::$defaultWindow,
            ];
        }
        
        self::$limits[$key]['attempts']++;
    }
    
    /**
     * Reset rate limit for a key
     * 
     * @param string $key
     */
    public static function reset(string $key): void
    {
        unset(self::$limits[$key]);
    }
    
    /**
     * Get current limit status for a key
     * 
     * @param string $key
     * @return array|null
     */
    public static function getStatus(string $key): ?array
    {
        return self::$limits[$key] ?? null;
    }
}
