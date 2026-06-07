<?php
/**
 * Base Test Case Class
 * 
 * Provides common functionality for all unit tests.
 */

namespace Omurga\Tests\Helpers;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any global state
        if (isset($GLOBALS['omurga_hooks'])) {
            unset($GLOBALS['omurga_hooks']);
        }
        if (isset($GLOBALS['omurga_plugin_permissions'])) {
            unset($GLOBALS['omurga_plugin_permissions']);
        }
    }
    
    /**
     * Tear down test environment
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up
    }
    
    /**
     * Assert that a value is a valid email
     * 
     * @param string $email
     */
    protected function assertValidEmail(string $email): void
    {
        $this->assertTrue(
            filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
            "$email is not a valid email address"
        );
    }
    
    /**
     * Assert that a string is HTML escaped
     * 
     * @param string $string
     */
    protected function assertIsHtmlEscaped(string $string): void
    {
        $this->assertTrue(
            strpos($string, '<') === false || strpos($string, '&lt;') !== false,
            "String appears to contain unescaped HTML: $string"
        );
    }
    
    /**
     * Assert that an array has required keys
     * 
     * @param array $array
     * @param array $requiredKeys
     */
    protected function assertHasRequiredKeys(array $array, array $requiredKeys): void
    {
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $array,
                "Required key '$key' is missing from array"
            );
        }
    }
}