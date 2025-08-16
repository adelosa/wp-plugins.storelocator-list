/**
 * Store Update Page JavaScript
 * 
 * @package StoreLocator-List
 * @since 0.2.0
 */

(function($) {
    'use strict';
    
    /**
     * Store Update functionality
     */
    var StoreUpdate = {
        
        /**
         * Initialize the store update functionality
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Authentication form submission
            $(document).on('submit', '#sllist-auth-form', this.handleAuthentication);
            
            // Store update form submission
            $(document).on('submit', '#sllist-update-form', this.handleStoreUpdate);
            
            // Input validation
            $(document).on('blur', 'input[type="email"]', this.validateEmail);
            $(document).on('blur', 'input[type="url"]', this.validateUrl);
        },
        
        /**
         * Handle authentication form submission
         */
        handleAuthentication: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();
            
            // Disable form and show loading
            $form.addClass('sllist-loading');
            $submitBtn.prop('disabled', true).text(sllist_update.strings.authenticating);
            
            // Clear any existing errors
            $('.sllist-error').remove();
            
            var formData = {
                action: 'sllist_authenticate_store_access',
                sllist_auth_nonce: $form.find('input[name="sllist_auth_nonce"]').val(),
                token: $form.find('input[name="token"]').val(),
                access_password: $form.find('input[name="access_password"]').val()
            };
            
            $.ajax({
                url: sllist_update.ajax_url,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Replace the authentication form with the update form
                        $('.sllist-authentication-form').fadeOut(300, function() {
                            $(this).html(response.data.html).fadeIn(300);
                        });
                    } else {
                        StoreUpdate.showError($form, response.data);
                    }
                },
                error: function() {
                    StoreUpdate.showError($form, sllist_update.strings.error);
                },
                complete: function() {
                    $form.removeClass('sllist-loading');
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        },
        
        /**
         * Handle store update form submission
         */
        handleStoreUpdate: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.text();
            
            // Validate form before submitting
            if (!StoreUpdate.validateForm($form)) {
                return;
            }
            
            // Disable form and show loading
            $form.addClass('sllist-loading');
            $submitBtn.prop('disabled', true).text(sllist_update.strings.updating);
            
            // Clear any existing errors
            $('.sllist-error').remove();
            
            var formData = $form.serialize() + '&action=sllist_update_store_details';
            
            $.ajax({
                url: sllist_update.ajax_url,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        StoreUpdate.showSuccess($form, response.data);
                        // Disable form after successful update
                        $form.find('input, textarea, button').prop('disabled', true);
                    } else {
                        StoreUpdate.showError($form, response.data);
                    }
                },
                error: function() {
                    StoreUpdate.showError($form, sllist_update.strings.error);
                },
                complete: function() {
                    $form.removeClass('sllist-loading');
                    if (!$submitBtn.prop('disabled')) {
                        $submitBtn.text(originalText);
                    }
                }
            });
        },
        
        /**
         * Validate the entire form
         */
        validateForm: function($form) {
            var isValid = true;
            
            // Clear existing validation errors
            $('.field-error').remove();
            $form.find('.error').removeClass('error');
            
            // Validate required fields
            $form.find('input[required], textarea[required]').each(function() {
                var $field = $(this);
                if (!$field.val().trim()) {
                    StoreUpdate.showFieldError($field, 'This field is required.');
                    isValid = false;
                }
            });
            
            // Validate email
            var $emailField = $form.find('input[type="email"]');
            if ($emailField.length && $emailField.val()) {
                if (!StoreUpdate.isValidEmail($emailField.val())) {
                    StoreUpdate.showFieldError($emailField, 'Please enter a valid email address.');
                    isValid = false;
                }
            }
            
            // Validate URL
            var $urlField = $form.find('input[type="url"]');
            if ($urlField.length && $urlField.val()) {
                if (!StoreUpdate.isValidUrl($urlField.val())) {
                    StoreUpdate.showFieldError($urlField, 'Please enter a valid URL.');
                    isValid = false;
                }
            }
            
            return isValid;
        },
        
        /**
         * Validate email field on blur
         */
        validateEmail: function() {
            var $field = $(this);
            var email = $field.val().trim();
            
            if (email && !StoreUpdate.isValidEmail(email)) {
                StoreUpdate.showFieldError($field, 'Please enter a valid email address.');
            } else {
                StoreUpdate.clearFieldError($field);
            }
        },
        
        /**
         * Validate URL field on blur
         */
        validateUrl: function() {
            var $field = $(this);
            var url = $field.val().trim();
            
            if (url && !StoreUpdate.isValidUrl(url)) {
                StoreUpdate.showFieldError($field, 'Please enter a valid URL (e.g., https://example.com).');
            } else {
                StoreUpdate.clearFieldError($field);
            }
        },
        
        /**
         * Check if email is valid
         */
        isValidEmail: function(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },
        
        /**
         * Check if URL is valid
         */
        isValidUrl: function(url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        },
        
        /**
         * Show form error message
         */
        showError: function($form, message) {
            var $error = $('<div class="sllist-error" style="background: #fbeaea; border: 1px solid #dc3232; border-radius: 4px; padding: 15px; margin: 15px 0; color: #dc3232;"></div>')
                .text(message);
            
            $form.prepend($error);
            
            // Scroll to error
            $('html, body').animate({
                scrollTop: $error.offset().top - 20
            }, 300);
        },
        
        /**
         * Show success message
         */
        showSuccess: function($form, message) {
            var $success = $('<div class="sllist-success" style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; padding: 15px; margin: 15px 0; color: #155724;"></div>')
                .html('<strong>Success!</strong> ' + message);
            
            $form.prepend($success);
            
            // Scroll to success message
            $('html, body').animate({
                scrollTop: $success.offset().top - 20
            }, 300);
        },
        
        /**
         * Show field-specific error
         */
        showFieldError: function($field, message) {
            // Clear existing error for this field
            StoreUpdate.clearFieldError($field);
            
            // Add error class to field
            $field.addClass('error').css('border-color', '#dc3232');
            
            // Add error message
            var $error = $('<div class="field-error" style="color: #dc3232; font-size: 12px; margin-top: 5px;"></div>')
                .text(message);
            
            $field.closest('.form-group').append($error);
        },
        
        /**
         * Clear field error
         */
        clearFieldError: function($field) {
            $field.removeClass('error').css('border-color', '');
            $field.closest('.form-group').find('.field-error').remove();
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        StoreUpdate.init();
    });
    
})(jQuery);
