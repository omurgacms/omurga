# Testing Guide

## Running Tests

### Prerequisites

```bash
composer require --dev phpunit/phpunit ^9.0
composer require --dev squizlabs/php_codesniffer
```

### Run All Tests

```bash
vendor/bin/phpunit
```

### Run Specific Test Suite

```bash
# Unit tests only
vendor/bin/phpunit --testsuite="Unit Tests"

# Integration tests only
vendor/bin/phpunit --testsuite="Integration Tests"

# Security tests only
vendor/bin/phpunit --testsuite="Security Tests"
```

### Run Specific Test File

```bash
vendor/bin/phpunit tests/Unit/Validation/ValidationServiceTest.php
```

### Generate Coverage Report

```bash
vendor/bin/phpunit --coverage-html=coverage/html
```

## Code Quality

### Check Code Standards

```bash
vendor/bin/phpcs
```

### Fix Code Standards Automatically

```bash
vendor/bin/phpcbf
```

## Writing Tests

### Test Structure

```php
<?php
namespace Omurga\Tests\Unit\YourModule;

use Omurga\Tests\Helpers\TestCase;

class YourTest extends TestCase
{
    public function testSomethingWorks()
    {
        $result = someFunction();
        $this->assertTrue($result);
    }
}
```

### Test Naming Conventions

- Class: `{Feature}Test.php`
- Method: `test{WhatIsBeingTested}`
- Example: `testValidEmailValidation()`

## Continuous Integration

Add this to `.github/workflows/tests.yml` for GitHub Actions:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    - uses: shivammathur/setup-php@v2
      with:
        php-version: 7.4
    - run: composer install
    - run: vendor/bin/phpunit
    - run: vendor/bin/phpcs
```

## Best Practices

1. **Test Isolation**: Each test should be independent
2. **Clear Names**: Test names should describe what they test
3. **Arrange-Act-Assert**: Structure tests with setup, execution, verification
4. **Mock External Dependencies**: Use mocks for database, API calls, etc.
5. **Test Edge Cases**: Don't just test happy paths
6. **Keep Tests Fast**: Unit tests should run quickly
