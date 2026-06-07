<?php
/**
 * Unit Tests for RateLimiter
 */

namespace Omurga\Tests\Unit\Security;

use Omurga\Tests\Helpers\TestCase;
use Omurga\Security\RateLimiter;

class RateLimiterTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up rate limit data
        RateLimiter::reset('test-key');
    }
    
    public function testRateLimitAllowsInitialAttempts()
    {
        $result = RateLimiter::check('test-key', 5, 3600);
        
        $this->assertTrue($result['allowed']);
        $this->assertEquals(5, $result['remaining']);
    }
    
    public function testRateLimitRecordsAttempt()
    {
        RateLimiter::recordAttempt('test-key');
        $result = RateLimiter::check('test-key', 5, 3600);
        
        $this->assertTrue($result['allowed']);
        $this->assertEquals(5, $result['remaining']);
    }
    
    public function testRateLimitBlocksAfterMaxAttempts()
    {
        $key = 'test-key';
        $maxAttempts = 3;
        
        // Record 3 attempts
        for ($i = 0; $i < 3; $i++) {
            RateLimiter::recordAttempt($key);
        }
        
        $result = RateLimiter::check($key, $maxAttempts, 3600);
        
        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
    }
    
    public function testRateLimitResetsKey()
    {
        $key = 'test-key';
        
        RateLimiter::recordAttempt($key);
        RateLimiter::reset($key);
        
        $result = RateLimiter::check($key, 5, 3600);
        
        $this->assertTrue($result['allowed']);
    }
    
    public function testRateLimitGetStatus()
    {
        $key = 'test-key';
        RateLimiter::recordAttempt($key);
        
        $status = RateLimiter::getStatus($key);
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('attempts', $status);
        $this->assertArrayHasKey('resetAt', $status);
    }
}
