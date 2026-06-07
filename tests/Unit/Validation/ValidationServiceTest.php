<?php
/**
 * Unit Tests for ValidationService
 */

namespace Omurga\Tests\Unit\Validation;

use Omurga\Tests\Helpers\TestCase;
use Omurga\Validation\ValidationService;

class ValidationServiceTest extends TestCase
{
    /**
     * @var ValidationService
     */
    private $validator;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ValidationService();
    }
    
    public function testRequiredValidation()
    {
        $data = ['email' => ''];
        $rules = ['email' => 'required'];
        
        $this->assertFalse($this->validator->validate($data, $rules));
        $this->assertTrue($this->validator->hasError('email'));
    }
    
    public function testEmailValidation()
    {
        $data = ['email' => 'invalid-email'];
        $rules = ['email' => 'email'];
        
        $this->assertFalse($this->validator->validate($data, $rules));
    }
    
    public function testValidEmailValidation()
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'email'];
        
        $this->assertTrue($this->validator->validate($data, $rules));
    }
    
    public function testMinLengthValidation()
    {
        $data = ['name' => 'Jo'];
        $rules = ['name' => 'min:3'];
        
        $this->assertFalse($this->validator->validate($data, $rules));
    }
    
    public function testMaxLengthValidation()
    {
        $data = ['name' => 'This is a very long name that exceeds the maximum'];
        $rules = ['name' => 'max:10'];
        
        $this->assertFalse($this->validator->validate($data, $rules));
    }
    
    public function testNumericValidation()
    {
        $data = ['age' => 'not-a-number'];
        $rules = ['age' => 'numeric'];
        
        $this->assertFalse($this->validator->validate($data, $rules));
    }
    
    public function testSlugValidation()
    {
        $data = ['slug' => 'valid-slug_123'];
        $rules = ['slug' => 'slug'];
        
        $this->assertTrue($this->validator->validate($data, $rules));
    }
    
    public function testInvalidSlugValidation()
    {
        $data = ['slug' => 'invalid slug!'];
        $rules = ['slug' => 'slug'];
        
        $this->assertFalse($this->validator->validate($data, $rules));
    }
    
    public function testMultipleRules()
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'required|email|max:100'];
        
        $this->assertTrue($this->validator->validate($data, $rules));
    }
    
    public function testGetValidatedData()
    {
        $data = ['email' => 'test@example.com', 'name' => 'Test User'];
        $rules = [
            'email' => 'required|email',
            'name' => 'required',
        ];
        
        $this->validator->validate($data, $rules);
        $validated = $this->validator->getValidated();
        
        $this->assertArrayHasKey('email', $validated);
        $this->assertEquals('test@example.com', $validated['email']);
    }
}
