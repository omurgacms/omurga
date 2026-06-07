<?php
/**
 * Validation Rules Registry
 * 
 * Provides common validation rule sets for different content types.
 */

namespace Omurga\Validation;

class ValidationRules
{
    /**
     * Rules for user registration
     * 
     * @return array
     */
    public static function userRegistration(): array
    {
        return [
            'email' => 'required|email|max:190',
            'password' => 'required|min:8|max:255',
            'password_confirm' => 'required',
            'name' => 'required|min:2|max:120',
        ];
    }
    
    /**
     * Rules for user login
     * 
     * @return array
     */
    public static function userLogin(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|min:1',
        ];
    }
    
    /**
     * Rules for post creation/update
     * 
     * @return array
     */
    public static function postContent(): array
    {
        return [
            'title' => 'required|min:3|max:255',
            'slug' => 'required|slug|max:255',
            'content' => 'required|min:10',
            'status' => 'required',
            'category_id' => 'numeric',
        ];
    }
    
    /**
     * Rules for comment submission
     * 
     * @return array
     */
    public static function commentSubmission(): array
    {
        return [
            'author_name' => 'required|min:2|max:120',
            'author_email' => 'required|email|max:190',
            'content' => 'required|min:2|max:3000',
            'post_id' => 'required|numeric',
        ];
    }
    
    /**
     * Rules for form submission
     * 
     * @return array
     */
    public static function formSubmission(): array
    {
        return [
            'form_id' => 'required|numeric',
            'submit_data' => 'required',
        ];
    }
    
    /**
     * Rules for theme metadata
     * 
     * @return array
     */
    public static function themeMetadata(): array
    {
        return [
            'name' => 'required|min:3|max:255',
            'slug' => 'required|slug',
            'version' => 'required',
            'author' => 'required|min:2|max:255',
        ];
    }
    
    /**
     * Rules for package metadata
     * 
     * @return array
     */
    public static function packageMetadata(): array
    {
        return [
            'name' => 'required|min:3|max:255',
            'slug' => 'required|slug',
            'version' => 'required',
            'author' => 'required|min:2|max:255',
            'permissions' => 'required',
        ];
    }
}
