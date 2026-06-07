<?php
/**
 * Structured Logger
 * 
 * Provides structured logging for debugging, auditing, and monitoring.
 */

namespace Omurga\Logging;

use DateTime;

class Logger
{
    /**
     * Log levels
     */
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';
    
    /**
     * @var string Log directory
     */
    private $logDir;
    
    /**
     * @var string Current log file
     */
    private $logFile;
    
    /**
     * Constructor
     * 
     * @param string|null $logDir
     */
    public function __construct(?string $logDir = null)
    {
        $this->logDir = $logDir ?? (defined('OMURGA_ROOT') ? OMURGA_ROOT . '/storage/logs' : './logs');
        
        // Create log directory if it doesn't exist
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0775, true);
        }
        
        $this->logFile = $this->logDir . '/omurga-' . date('Y-m-d') . '.log';
    }
    
    /**
     * Log debug message
     * 
     * @param string $message
     * @param array $context
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Log info message
     * 
     * @param string $message
     * @param array $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log warning message
     * 
     * @param string $message
     * @param array $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Log error message
     * 
     * @param string $message
     * @param array $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Log critical message
     * 
     * @param string $message
     * @param array $context
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }
    
    /**
     * Log exception
     * 
     * @param Throwable $exception
     * @param string|null $message
     */
    public function exception($exception, ?string $message = null): void
    {
        $context = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString(),
        ];
        
        $this->log(
            self::LEVEL_ERROR,
            $message ?? 'Exception: ' . get_class($exception),
            $context
        );
    }
    
    /**
     * Log audit event
     * 
     * @param string $action
     * @param string $resource
     * @param int|null $userId
     * @param array $details
     */
    public function audit(string $action, string $resource, ?int $userId = null, array $details = []): void
    {
        $context = array_merge(
            [
                'action' => $action,
                'resource' => $resource,
                'userId' => $userId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ],
            $details
        );
        
        $this->log(self::LEVEL_INFO, "Audit: $action on $resource", $context);
    }
    
    /**
     * Write log entry
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     */
    private function log(string $level, string $message, array $context = []): void
    {
        $timestamp = (new DateTime())->format('Y-m-d H:i:s.u');
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        
        @file_put_contents($this->logFile, $logLine, FILE_APPEND);
    }
    
    /**
     * Get recent logs
     * 
     * @param int $lines
     * @return array
     */
    public function getRecent(int $lines = 100): array
    {
        if (!is_file($this->logFile)) {
            return [];
        }
        
        $logs = [];
        $file = new \SplFileObject($this->logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $lineCount = $file->key();
        
        $start = max(0, $lineCount - $lines);
        $file->seek($start);
        
        foreach ($file as $line) {
            if (trim($line)) {
                $logs[] = json_decode($line, true);
            }
        }
        
        return $logs;
    }
}
