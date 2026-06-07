<?php
/**
 * PHPUnit Bootstrap File
 * 
 * Sets up the test environment and loads necessary dependencies.
 */

// Define test mode
define('OMURGA_TESTING', true);
define('OMURGA_ROOT', dirname(__DIR__));
define('OMURGA_VERSION', '1.0.8-beta');
define('OMURGA_SCHEMA_VERSION', '4.0.0');
define('OMURGA_INIT', true);

// Suppress error display in tests
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Autoload composer dependencies if available
$composer_autoload = OMURGA_ROOT . '/vendor/autoload.php';
if (file_exists($composer_autoload)) {
    require_once $composer_autoload;
}

// Load core files
require_once OMURGA_ROOT . '/core/hooks.php';
require_once OMURGA_ROOT . '/core/validation/ValidationService.php';
require_once OMURGA_ROOT . '/core/validation/ValidationRules.php';
require_once OMURGA_ROOT . '/core/security/SecurityValidator.php';
require_once OMURGA_ROOT . '/core/logging/Logger.php';
require_once OMURGA_ROOT . '/core/security/RateLimiter.php';

// Load test helpers
require_once __DIR__ . '/Helpers/TestCase.php';
require_once __DIR__ . '/Helpers/DatabaseTestCase.php';
require_once __DIR__ . '/Helpers/MockDatabase.php';