<?php
/**
 * Validation Service
 * 
 * Centralized service for validating user input across the application.
 * Provides methods for common validation scenarios.
 */

namespace Omurga\Validation;

class ValidationService
{
    /**
     * @var array Validation errors
     */
    private $errors = [];
    
    /**
     * @var array Validated data
     */
    private $validated = [];
    
    /**
     * Validate input data against rules
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return bool True if validation passes
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];
        $this->validated = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            if (!$this->validateField($field, $value, $fieldRules)) {
                continue;
            }
            
            $this->validated[$field] = $value;
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate a single field
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string|array $rules Validation rules
     * @return bool
     */
    private function validateField(string $field, $value, $rules): bool
    {
        $rules = is_string($rules) ? explode('|', $rules) : (array)$rules;
        
        foreach ($rules as $rule) {
            $rule = trim($rule);
            
            // Parse rule with parameters: required:12,100
            if (strpos($rule, ':') !== false) {
                [$ruleName, $params] = explode(':', $rule, 2);
                $params = explode(',', $params);
            } else {
                $ruleName = $rule;
                $params = [];
            }
            
            if (!$this->applyRule($field, $value, $ruleName, $params)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Apply a validation rule
     * 
     * @param string $field
     * @param mixed $value
     * @param string $rule
     * @param array $params
     * @return bool
     */
    private function applyRule(string $field, $value, string $rule, array $params): bool
    {
        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, "$field is required");
                    return false;
                }
                break;
            
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "$field must be a valid email");
                    return false;
                }
                break;
            
            case 'min':
                $min = $params[0] ?? 0;
                if (strlen((string)$value) < $min) {
                    $this->addError($field, "$field must be at least $min characters");
                    return false;
                }
                break;
            
            case 'max':
                $max = $params[0] ?? PHP_INT_MAX;
                if (strlen((string)$value) > $max) {
                    $this->addError($field, "$field must not exceed $max characters");
                    return false;
                }
                break;
            
            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, "$field must be numeric");
                    return false;
                }
                break;
            
            case 'slug':
                if (!preg_match('/^[a-z0-9\-_]+$/i', (string)$value)) {
                    $this->addError($field, "$field must contain only letters, numbers, hyphens, and underscores");
                    return false;
                }
                break;
            
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "$field must be a valid URL");
                    return false;
                }
                break;
            
            case 'safe_html':
                // Basic check - should be expanded based on needs
                if (preg_match('/<script|javascript:|onerror=/i', (string)$value)) {
                    $this->addError($field, "$field contains invalid HTML");
                    return false;
                }
                break;
        }
        
        return true;
    }
    
    /**
     * Add validation error
     * 
     * @param string $field
     * @param string $message
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    /**
     * Get all validation errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get validated data
     * 
     * @return array
     */
    public function getValidated(): array
    {
        return $this->validated;
    }
    
    /**
     * Check if a specific field has errors
     * 
     * @param string $field
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }
}
