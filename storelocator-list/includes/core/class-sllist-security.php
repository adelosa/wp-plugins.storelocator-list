<?php
/**
 * Security and Token Management functionality
 * 
 * @package StoreLocator-List
 * @since 0.2.0
 */

namespace StoreLocatorList\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SLList_Security {
    
    /**
     * Meta key constants for token storage
     */
    const META_ACCESS_TOKEN = 'sllist_access_token';
    const META_PASSWORD_HASH = 'sllist_access_password_hash';
    const META_TOKEN_EXPIRES = 'sllist_token_expires';
    const META_TOKEN_USED = 'sllist_token_used';
    const META_ACCESS_REQUESTED_DATE = 'sllist_access_requested_date';
    const META_LAST_ACCESS_ATTEMPT = 'sllist_last_access_attempt';
    const META_ACCESS_ATTEMPTS = 'sllist_access_attempts';
    
    /**
     * Security constants
     */
    const TOKEN_EXPIRY_HOURS = 24; // Token expires after 24 hours
    const MAX_ACCESS_ATTEMPTS = 5; // Maximum failed access attempts
    const RATE_LIMIT_WINDOW = 3600; // 1 hour rate limit window
    const TOKEN_LENGTH = 32; // Length of generated tokens
    const PASSWORD_LENGTH = 12; // Length of generated passwords
    
    /**
     * Generate a secure access token
     * 
     * @return string Secure UUID-style token
     */
    public static function generate_access_token() {
        // Generate a secure random token using WordPress's wp_generate_password
        $token = \wp_generate_password(self::TOKEN_LENGTH, false, false);
        
        // Add timestamp to ensure uniqueness
        $unique_token = hash('sha256', $token . time() . \wp_rand());
        
        // Format as UUID-style (8-4-4-4-12)
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($unique_token, 0, 8),
            substr($unique_token, 8, 4),
            substr($unique_token, 12, 4),
            substr($unique_token, 16, 4),
            substr($unique_token, 20, 12)
        );
    }
    
    /**
     * Generate a secure random password
     * 
     * @return string Random password
     */
    public static function generate_access_password() {
        // Generate a password with mixed case, numbers, but no special chars for user-friendliness
        return \wp_generate_password(self::PASSWORD_LENGTH, false, false);
    }
    
    /**
     * Hash a password securely
     * 
     * @param string $password Plain text password
     * @return string Hashed password
     */
    public static function hash_password($password) {
        return \wp_hash_password($password);
    }
    
    /**
     * Verify a password against its hash
     * 
     * @param string $password Plain text password
     * @param string $hash Hashed password
     * @return bool True if password matches
     */
    public static function verify_password($password, $hash) {
        return \wp_check_password($password, $hash);
    }
    
    /**
     * Create access credentials for a store
     * 
     * @param int $store_id Store post ID
     * @return array|false Array with token and password, or false on failure
     */
    public static function create_store_access_credentials($store_id) {
        $store_id = intval($store_id);
        
        if ($store_id <= 0) {
            return false;
        }
        
        // Verify this is a valid store post
        $store = \get_post($store_id);
        if (!$store || $store->post_type !== 'wpsl_stores') {
            return false;
        }
        
        // Revoke any existing tokens first
        self::revoke_store_access($store_id);
        
        // Generate new credentials
        $token = self::generate_access_token();
        $password = self::generate_access_password();
        $password_hash = self::hash_password($password);
        $expires = time() + (self::TOKEN_EXPIRY_HOURS * HOUR_IN_SECONDS);
        $requested_date = \current_time('mysql');
        
        // Store credentials as post meta
        $meta_updates = [
            self::META_ACCESS_TOKEN => $token,
            self::META_PASSWORD_HASH => $password_hash,
            self::META_TOKEN_EXPIRES => $expires,
            self::META_TOKEN_USED => false,
            self::META_ACCESS_REQUESTED_DATE => $requested_date,
            self::META_ACCESS_ATTEMPTS => 0
        ];
        
        $success = true;
        foreach ($meta_updates as $meta_key => $meta_value) {
            if (!\update_post_meta($store_id, $meta_key, $meta_value)) {
                $success = false;
                break;
            }
        }
        
        if (!$success) {
            // Clean up if any update failed
            self::revoke_store_access($store_id);
            return false;
        }
        
        return [
            'token' => $token,
            'password' => $password,
            'expires' => $expires,
            'store_id' => $store_id
        ];
    }
    
    /**
     * Validate access credentials
     * 
     * @param string $token Access token
     * @param string $password Plain text password
     * @return array|false Store data if valid, false if invalid
     */
    public static function validate_access_credentials($token, $password) {
        if (empty($token) || empty($password)) {
            return false;
        }
        
        // Find store by token
        $stores = \get_posts([
            'post_type' => 'wpsl_stores',
            'meta_key' => self::META_ACCESS_TOKEN,
            'meta_value' => \sanitize_text_field($token),
            'meta_compare' => '=',
            'posts_per_page' => 1,
            'post_status' => ['publish', 'pending']
        ]);
        
        if (empty($stores)) {
            return false;
        }
        
        $store = $stores[0];
        $store_id = $store->ID;
        
        // Check if token is already used
        $token_used = \get_post_meta($store_id, self::META_TOKEN_USED, true);
        if ($token_used) {
            return false;
        }
        
        // Check if token is expired
        $expires = \get_post_meta($store_id, self::META_TOKEN_EXPIRES, true);
        if ($expires && time() > $expires) {
            self::revoke_store_access($store_id);
            return false;
        }
        
        // Check access attempts (rate limiting)
        $attempts = \get_post_meta($store_id, self::META_ACCESS_ATTEMPTS, true) ?: 0;
        if ($attempts >= self::MAX_ACCESS_ATTEMPTS) {
            return false;
        }
        
        // Verify password
        $password_hash = \get_post_meta($store_id, self::META_PASSWORD_HASH, true);
        if (!self::verify_password($password, $password_hash)) {
            // Increment failed attempts
            \update_post_meta($store_id, self::META_ACCESS_ATTEMPTS, $attempts + 1);
            \update_post_meta($store_id, self::META_LAST_ACCESS_ATTEMPT, \current_time('mysql'));
            return false;
        }
        
        // Valid credentials - reset attempts counter
        \update_post_meta($store_id, self::META_ACCESS_ATTEMPTS, 0);
        \update_post_meta($store_id, self::META_LAST_ACCESS_ATTEMPT, \current_time('mysql'));
        
        return [
            'store_id' => $store_id,
            'store' => $store,
            'token' => $token,
            'expires' => $expires
        ];
    }
    
    /**
     * Mark token as used (invalidate it)
     * 
     * @param int $store_id Store post ID
     * @return bool Success status
     */
    public static function mark_token_used($store_id) {
        $store_id = intval($store_id);
        if ($store_id <= 0) {
            return false;
        }
        
        return \update_post_meta($store_id, self::META_TOKEN_USED, true);
    }
    
    /**
     * Revoke access credentials for a store
     * 
     * @param int $store_id Store post ID
     * @return bool Success status
     */
    public static function revoke_store_access($store_id) {
        $store_id = intval($store_id);
        if ($store_id <= 0) {
            return false;
        }
        
        $meta_keys = [
            self::META_ACCESS_TOKEN,
            self::META_PASSWORD_HASH,
            self::META_TOKEN_EXPIRES,
            self::META_TOKEN_USED,
            self::META_ACCESS_ATTEMPTS,
            self::META_LAST_ACCESS_ATTEMPT
        ];
        
        $success = true;
        foreach ($meta_keys as $meta_key) {
            if (!\delete_post_meta($store_id, $meta_key)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Get access status for a store
     * 
     * @param int $store_id Store post ID
     * @return array|false Access status information or false if no access
     */
    public static function get_store_access_status($store_id) {
        $store_id = intval($store_id);
        if ($store_id <= 0) {
            return false;
        }
        
        $token = \get_post_meta($store_id, self::META_ACCESS_TOKEN, true);
        if (empty($token)) {
            return false;
        }
        
        $expires = \get_post_meta($store_id, self::META_TOKEN_EXPIRES, true);
        $used = \get_post_meta($store_id, self::META_TOKEN_USED, true);
        $requested_date = \get_post_meta($store_id, self::META_ACCESS_REQUESTED_DATE, true);
        $attempts = \get_post_meta($store_id, self::META_ACCESS_ATTEMPTS, true) ?: 0;
        $last_attempt = \get_post_meta($store_id, self::META_LAST_ACCESS_ATTEMPT, true);
        
        $is_expired = $expires && time() > $expires;
        $is_locked = $attempts >= self::MAX_ACCESS_ATTEMPTS;
        
        return [
            'has_access' => true,
            'token' => $token,
            'expires' => $expires,
            'expires_formatted' => $expires ? date('Y-m-d H:i:s', $expires) : null,
            'is_expired' => $is_expired,
            'is_used' => (bool)$used,
            'is_locked' => $is_locked,
            'requested_date' => $requested_date,
            'attempts' => $attempts,
            'last_attempt' => $last_attempt,
            'is_active' => !$is_expired && !$used && !$is_locked
        ];
    }
    
    /**
     * Clean up expired tokens (to be run via cron)
     * 
     * @return int Number of tokens cleaned up
     */
    public static function cleanup_expired_tokens() {
        global $wpdb;
        
        $current_time = time();
        $cleaned_count = 0;
        
        // Find all stores with expired tokens
        $expired_stores = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = %s 
             AND meta_value < %d",
            self::META_TOKEN_EXPIRES,
            $current_time
        ));
        
        foreach ($expired_stores as $store_data) {
            if (self::revoke_store_access($store_data->post_id)) {
                $cleaned_count++;
            }
        }
        
        return $cleaned_count;
    }
    
    /**
     * Sanitize and validate email address
     * 
     * @param string $email Email address
     * @return string|false Sanitized email or false if invalid
     */
    public static function sanitize_email($email) {
        $email = \sanitize_email($email);
        return \is_email($email) ? $email : false;
    }
    
    /**
     * Rate limiting check for IP address
     * 
     * @param string $action Action being rate limited
     * @param string $ip_address IP address (optional, uses current IP if not provided)
     * @return bool True if rate limit exceeded
     */
    public static function is_rate_limited($action = 'general', $ip_address = null) {
        if (!$ip_address) {
            $ip_address = self::get_client_ip();
        }
        
        $transient_key = 'sllist_rate_limit_' . md5($action . $ip_address);
        $attempts = \get_transient($transient_key) ?: 0;
        
        // Allow 10 attempts per hour for most actions
        $max_attempts = ($action === 'access_request') ? 5 : 10;
        
        return $attempts >= $max_attempts;
    }
    
    /**
     * Record rate limiting attempt
     * 
     * @param string $action Action being rate limited
     * @param string $ip_address IP address (optional, uses current IP if not provided)
     */
    public static function record_rate_limit_attempt($action = 'general', $ip_address = null) {
        if (!$ip_address) {
            $ip_address = self::get_client_ip();
        }
        
        $transient_key = 'sllist_rate_limit_' . md5($action . $ip_address);
        $attempts = \get_transient($transient_key) ?: 0;
        
        \set_transient($transient_key, $attempts + 1, self::RATE_LIMIT_WINDOW);
    }
    
    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    public static function get_client_ip() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
