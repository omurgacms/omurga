<?php
/**
 * Mock Database for Testing
 * 
 * Provides a mock PDO instance for unit tests that don't need a real database.
 */

namespace Omurga\Tests\Helpers;

use PHPUnit\Framework\MockObject\MockObject;

class MockDatabase
{
    /**
     * Create a mock PDO instance
     * 
     * @return MockObject
     */
    public static function createMockPDO()
    {
        $mockPDO = self::getMockBuilder('\\PDO')
            ->disableOriginalConstructor()
            ->getMock();
        
        return $mockPDO;
    }
    
    /**
     * Create a mock prepared statement
     * 
     * @param array $results
     * @return MockObject
     */
    public static function createMockStatement(array $results = [])
    {
        $mockStmt = self::getMockBuilder('\\PDOStatement')
            ->disableOriginalConstructor()
            ->getMock();
        
        $mockStmt->method('execute')
            ->willReturn(true);
        
        $mockStmt->method('fetch')
            ->willReturn($results ? array_shift($results) : false);
        
        $mockStmt->method('fetchAll')
            ->willReturn($results);
        
        $mockStmt->method('fetchColumn')
            ->willReturn($results ? $results[0] : null);
        
        return $mockStmt;
    }
    
    /**
     * Set up mock for PDO::prepare()
     * 
     * @param MockObject $mockPDO
     * @param MockObject $mockStmt
     */
    public static function setupMockPrepare($mockPDO, $mockStmt)
    {
        $mockPDO->method('prepare')
            ->willReturn($mockStmt);
    }
}
