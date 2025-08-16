<?php
/**
 * Test Security Implementation
 * This file tests the database schema and security functions
 * 
 * Access via: http://localhost:8080/wp-content/plugins/storelocator-list/tests/test-security.php
 */

// Try to load WordPress first
$wp_load_paths = [
    dirname(__FILE__) . '/../../../../wp-load.php',
    dirname(__FILE__) . '/../../../wp-load.php',
    dirname(__FILE__) . '/../../wp-load.php'
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

// If WordPress isn't loaded, include minimal testing environment
if (!$wp_loaded) {
    if (!defined('ABSPATH')) {
        define('ABSPATH', '/tmp/test/');
        define('HOUR_IN_SECONDS', 3600);
    }
}

// Mock WordPress functions for testing
if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($special_chars) $chars .= '!@#$%^&*()';
        return substr(str_shuffle(str_repeat($chars, ceil($length/strlen($chars)))), 0, $length);
    }
}

if (!function_exists('wp_hash_password')) {
    function wp_hash_password($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}

if (!function_exists('wp_check_password')) {
    function wp_check_password($password, $hash) {
        return password_verify($password, $hash);
    }
}

if (!function_exists('wp_rand')) {
    function wp_rand($min = 0, $max = 0) {
        return mt_rand($min, $max);
    }
}

if (!function_exists('current_time')) {
    function current_time($type) {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('is_email')) {
    function is_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return strip_tags(trim($str));
    }
}

// Load our security class
require_once 'includes/core/class-sllist-security.php';

use StoreLocatorList\Core\SLList_Security;

echo "🔐 Security Implementation Test\n";
echo "===============================\n\n";

// Test 1: Token Generation
echo "1. Testing Token Generation:\n";
$token1 = SLList_Security::generate_access_token();
$token2 = SLList_Security::generate_access_token();

echo "   Token 1: {$token1}\n";
echo "   Token 2: {$token2}\n";
echo "   ✅ Tokens are unique: " . ($token1 !== $token2 ? "YES" : "NO") . "\n";
echo "   ✅ Token format: " . (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $token1) ? "VALID UUID" : "INVALID") . "\n";

// Test 2: Password Generation
echo "\n2. Testing Password Generation:\n";
$password1 = SLList_Security::generate_access_password();
$password2 = SLList_Security::generate_access_password();

echo "   Password 1: {$password1}\n";
echo "   Password 2: {$password2}\n";
echo "   ✅ Passwords are unique: " . ($password1 !== $password2 ? "YES" : "NO") . "\n";
echo "   ✅ Password length: " . strlen($password1) . " chars\n";

// Test 3: Password Hashing
echo "\n3. Testing Password Hashing:\n";
$plain_password = "TestPassword123";
$hashed = SLList_Security::hash_password($plain_password);

echo "   Plain password: {$plain_password}\n";
echo "   Hashed password: " . substr($hashed, 0, 30) . "...\n";
echo "   ✅ Hash verification (correct): " . (SLList_Security::verify_password($plain_password, $hashed) ? "PASS" : "FAIL") . "\n";
echo "   ✅ Hash verification (wrong): " . (SLList_Security::verify_password("WrongPassword", $hashed) ? "FAIL" : "PASS") . "\n";

// Test 4: Meta Key Constants
echo "\n4. Testing Meta Key Constants:\n";
$constants = [
    'META_ACCESS_TOKEN' => SLList_Security::META_ACCESS_TOKEN,
    'META_PASSWORD_HASH' => SLList_Security::META_PASSWORD_HASH,
    'META_TOKEN_EXPIRES' => SLList_Security::META_TOKEN_EXPIRES,
    'META_TOKEN_USED' => SLList_Security::META_TOKEN_USED,
    'META_ACCESS_REQUESTED_DATE' => SLList_Security::META_ACCESS_REQUESTED_DATE,
];

foreach ($constants as $name => $value) {
    echo "   {$name}: {$value}\n";
}

// Test 5: Security Constants
echo "\n5. Testing Security Constants:\n";
echo "   Token Expiry Hours: " . SLList_Security::TOKEN_EXPIRY_HOURS . "\n";
echo "   Max Access Attempts: " . SLList_Security::MAX_ACCESS_ATTEMPTS . "\n";
echo "   Rate Limit Window: " . SLList_Security::RATE_LIMIT_WINDOW . " seconds\n";
echo "   Token Length: " . SLList_Security::TOKEN_LENGTH . "\n";
echo "   Password Length: " . SLList_Security::PASSWORD_LENGTH . "\n";

// Test 6: Email Sanitization
echo "\n6. Testing Email Sanitization:\n";
$test_emails = [
    'valid@example.com',
    'invalid-email',
    'test@domain',
    'user+tag@example.org'
];

foreach ($test_emails as $email) {
    $sanitized = SLList_Security::sanitize_email($email);
    echo "   {$email} => " . ($sanitized ? $sanitized : "INVALID") . "\n";
}

// Test 7: IP Address Detection
echo "\n7. Testing IP Address Detection:\n";
$_SERVER['REMOTE_ADDR'] = '192.168.1.1';
$ip = SLList_Security::get_client_ip();
echo "   Detected IP: {$ip}\n";

echo "\n✅ All security tests completed!\n";
echo "\n📋 Database Schema Summary:\n";
echo "   - 6 meta fields per store for access control\n";
echo "   - Secure token generation with UUID format\n";
echo "   - BCrypt password hashing\n";
echo "   - Rate limiting and attempt tracking\n";
echo "   - Automatic token expiration\n";
echo "   - IP-based security monitoring\n";

echo "\n🚀 Ready for Phase 2 implementation!\n";
?>
