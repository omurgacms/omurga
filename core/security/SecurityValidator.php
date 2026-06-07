<?php
/**
 * Security Validator
 * 
 * Provides security-focused validation for sensitive operations.
 */

namespace Omurga\Security;

class SecurityValidator
{
    /**
     * Validate CSRF token
     * 
     * @param string $token Provided token
     * @param string|null $sessionToken Session token
     * @return bool
     */
    public static function validateCsrfToken(string $token, ?string $sessionToken): bool
    {
        if (empty($token) || empty($sessionToken)) {
            return false;
        }
        
        return hash_equals($sessionToken, $token);
    }
    
    /**
     * Validate password strength
     * 
     * @param string $password
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validatePasswordStrength(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain lowercase letters';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain uppercase letters';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain numbers';
        }
        
        if (!preg_match('/[!@#$%^&*()\-_=+\[\]{}|;:,.<>?]/', $password)) {
            $errors[] = 'Password must contain special characters';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
    
    /**
     * Validate file upload
     * 
     * @param array $file $_FILE entry
     * @param array $allowedMimes Allowed MIME types
     * @param int $maxSize Max file size in bytes
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateFileUpload(array $file, array $allowedMimes, int $maxSize): array
    {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = self::getUploadErrorMessage($file['error']);
        }
        
        if (!isset($file['name']) || empty($file['name'])) {
            $errors[] = 'No file provided';
        }
        
        if (isset($file['size']) && $file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed';
        }
        
        if (isset($file['tmp_name'])) {
            $mime = mime_content_type($file['tmp_name']);
            if (!in_array($mime, $allowedMimes)) {
                $errors[] = "File type '$mime' is not allowed";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
    
    /**
     * Get human-readable upload error message
     * 
     * @param int $errorCode
     * @return string
     */
    private static function getUploadErrorMessage(int $errorCode): string
    {
        $messages = [
            UPLOAD_ERR_OK => 'No error',
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'File upload blocked by extension',
        ];
        
        return $messages[$errorCode] ?? 'Unknown upload error';
    }
    
    /**
     * Validate SQL injection attempt
     * 
     * @param string $input
     * @return bool True if input appears safe
     */
    public static function isSafeSql(string $input): bool
    {
        $dangerousPatterns = [
            '/\b(UNION|SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|EXECUTE|SCRIPT)\b/i',
            '/--|#|;\/\*|\*\//i',
            '/xp_|sp_|exec|execute/i',
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Sanitize user input
     * 
     * @param string $input
     * @return string
     */
    public static function sanitizeInput(string $input): string
    {
        // Remove null bytes
        $input = str_replace('\0', '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        return $input;
    }
}
