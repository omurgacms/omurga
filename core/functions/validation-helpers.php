<?php
/**
 * Validation Helper Functions
 * 
 * Convenient functions for validation throughout the application.
 */

if (!function_exists('validate')) {
    /**
     * Quick validation helper
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return \Omurga\Validation\ValidationService
     */
    function validate(array $data, array $rules)
    {
        $validator = new \Omurga\Validation\ValidationService();
        $validator->validate($data, $rules);
        return $validator;
    }
}

if (!function_exists('sanitize_input')) {
    /**
     * Sanitize user input
     * 
     * @param string $input
     * @return string
     */
    function sanitize_input(string $input): string
    {
        return \Omurga\Security\SecurityValidator::sanitizeInput($input);
    }
}

if (!function_exists('validate_csrf')) {
    /**
     * Validate CSRF token
     * 
     * @param string $token
     * @param string|null $sessionToken
     * @return bool
     */
    function validate_csrf(string $token, ?string $sessionToken = null): bool
    {
        $sessionToken = $sessionToken ?? ($_SESSION['csrf_token'] ?? null);
        return \Omurga\Security\SecurityValidator::validateCsrfToken($token, $sessionToken);
    }
}

if (!function_exists('check_rate_limit')) {
    /**
     * Check if action is rate limited
     * 
     * @param string $key Unique identifier
     * @param int $maxAttempts Maximum attempts
     * @param int $windowSeconds Time window
     * @return array ['allowed' => bool, 'remaining' => int, 'resetAt' => int]
     */
    function check_rate_limit(string $key, int $maxAttempts, int $windowSeconds = 3600): array
    {
        return \Omurga\Security\RateLimiter::check($key, $maxAttempts, $windowSeconds);
    }
}

if (!function_exists('log_error')) {
    /**
     * Log an error
     * 
     * @param string $message
     * @param array $context
     */
    function log_error(string $message, array $context = []): void
    {
        $logger = new \Omurga\Logging\Logger();
        $logger->error($message, $context);
    }
}

if (!function_exists('log_audit')) {
    /**
     * Log an audit event
     * 
     * @param string $action
     * @param string $resource
     * @param int|null $userId
     * @param array $details
     */
    function log_audit(string $action, string $resource, ?int $userId = null, array $details = []): void
    {
        $logger = new \Omurga\Logging\Logger();
        $logger->audit($action, $resource, $userId, $details);
    }
}
