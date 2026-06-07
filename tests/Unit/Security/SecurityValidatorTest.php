<?php
/**
 * Unit Tests for SecurityValidator
 */

namespace Omurga\Tests\Unit\Security;

use Omurga\Tests\Helpers\TestCase;
use Omurga\Security\SecurityValidator;

class SecurityValidatorTest extends TestCase
{
    public function testCsrfTokenValidation()
    {
        $token = hash('sha256', random_bytes(32));
        
        $this->assertTrue(SecurityValidator::validateCsrfToken($token, $token));
        $this->assertFalse(SecurityValidator::validateCsrfToken($token, 'wrong-token'));
    }
    
    public function testEmptyCsrfTokenValidation()
    {
        $this->assertFalse(SecurityValidator::validateCsrfToken('', ''));
        $this->assertFalse(SecurityValidator::validateCsrfToken('token', ''));
    }
    
    public function testWeakPasswordValidation()
    {
        $result = SecurityValidator::validatePasswordStrength('weak');
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }
    
    public function testStrongPasswordValidation()
    {
        $result = SecurityValidator::validatePasswordStrength('StrongP@ss123');
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    public function testPasswordMissingUppercase()
    {
        $result = SecurityValidator::validatePasswordStrength('weakp@ss123');
        
        $this->assertFalse($result['valid']);
        $this->assertContains('uppercase', implode(' ', $result['errors']));
    }
    
    public function testPasswordMissingSpecialChar()
    {
        $result = SecurityValidator::validatePasswordStrength('WeakPass123');
        
        $this->assertFalse($result['valid']);
        $this->assertContains('special', implode(' ', $result['errors']));
    }
    
    public function testSqlInjectionDetection()
    {
        $this->assertFalse(SecurityValidator::isSafeSql("'; DROP TABLE users; --"));
        $this->assertFalse(SecurityValidator::isSafeSql("1 UNION SELECT * FROM users"));
        $this->assertTrue(SecurityValidator::isSafeSql("John Doe"));
    }
    
    public function testInputSanitization()
    {
        $input = "  test string with spaces  \0 and null bytes";
        $sanitized = SecurityValidator::sanitizeInput($input);
        
        $this->assertStringNotContainsString('\0', $sanitized);
        $this->assertEquals(trim($input), $sanitized);
    }
}
