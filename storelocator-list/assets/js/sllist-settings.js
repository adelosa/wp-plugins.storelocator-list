/**
 * SL List Settings JavaScript
 * 
 * @package StoreLocator-List
 * @since 0.2.2
 */

(function($) {
    'use strict';
    
    /**
     * Settings functionality
     */
    var SLListSettings = {
        
        /**
         * Initialize
         */
        init: function() {
            this.initColorPickers();
            this.bindEvents();
        },
        
        /**
         * Initialize color pickers
         */
        initColorPickers: function() {
            $('.sllist-color-picker').wpColorPicker({
                change: function(event, ui) {
                    // Handle color change if needed
                },
                clear: function() {
                    // Handle color clear if needed
                }
            });
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Field availability checkbox changes
            $(document).on('change', '.sllist-field-row input[type="checkbox"]', this.handleFieldToggle);
            
            // Permalink field validation
            $(document).on('blur', 'input[name*="permalink"]', this.validatePermalink);
            
            // Form submission validation
            $('form').on('submit', this.validateForm);
        },
        
        /**
         * Handle field toggle
         */
        handleFieldToggle: function() {
            var $checkbox = $(this);
            var $row = $checkbox.closest('.sllist-field-row');
            var $labelInput = $row.find('.sllist-field-label input[type="text"]');
            
            if ($checkbox.is(':checked')) {
                $labelInput.prop('disabled', false).removeClass('disabled');
                $row.removeClass('disabled');
            } else {
                $labelInput.prop('disabled', true).addClass('disabled');
                $row.addClass('disabled');
            }
        },
        
        /**
         * Validate permalink fields
         */
        validatePermalink: function() {
            var $input = $(this);
            var value = $input.val();
            
            // Clean the value
            var cleaned = value.toLowerCase()
                              .replace(/[^a-z0-9\-]/g, '-')
                              .replace(/-+/g, '-')
                              .replace(/^-|-$/g, '');
            
            if (value !== cleaned) {
                $input.val(cleaned);
                SLListSettings.showFieldWarning($input, 'Permalink cleaned: only letters, numbers, and hyphens are allowed.');
            }
        },
        
        /**
         * Validate form before submission
         */
        validateForm: function(e) {
            var isValid = true;
            var $form = $(this);
            
            // Clear previous warnings
            $('.sllist-field-warning').remove();
            
            // Validate required fields
            $form.find('input[required], textarea[required]').each(function() {
                var $field = $(this);
                if (!$field.val().trim()) {
                    SLListSettings.showFieldError($field, 'This field is required.');
                    isValid = false;
                }
            });
            
            // Validate permalinks
            $form.find('input[name*="permalink"]').each(function() {
                var $field = $(this);
                var value = $field.val();
                
                if (value && !/^[a-z0-9\-]+$/.test(value)) {
                    SLListSettings.showFieldError($field, 'Permalinks can only contain lowercase letters, numbers, and hyphens.');
                    isValid = false;
                }
            });
            
            // Validate colors
            $form.find('.sllist-color-picker').each(function() {
                var $field = $(this);
                var value = $field.val();
                
                if (value && !/^#[0-9A-Fa-f]{6}$/.test(value)) {
                    SLListSettings.showFieldError($field, 'Please enter a valid hex color code (e.g., #ffffff).');
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                SLListSettings.scrollToFirstError();
            }
        },
        
        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            SLListSettings.removeFieldMessage($field);
            
            var $error = $('<div class="sllist-field-warning error"></div>').text(message);
            $field.after($error);
            $field.addClass('error');
        },
        
        /**
         * Show field warning
         */
        showFieldWarning: function($field, message) {
            SLListSettings.removeFieldMessage($field);
            
            var $warning = $('<div class="sllist-field-warning warning"></div>').text(message);
            $field.after($warning);
            
            // Auto-remove warning after 3 seconds
            setTimeout(function() {
                $warning.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        },
        
        /**
         * Remove field message
         */
        removeFieldMessage: function($field) {
            $field.removeClass('error');
            $field.siblings('.sllist-field-warning').remove();
        },
        
        /**
         * Scroll to first error
         */
        scrollToFirstError: function() {
            var $firstError = $('.sllist-field-warning.error').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 300);
            }
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        SLListSettings.init();
    });
    
})(jQuery);

/* Additional CSS for validation messages */
document.addEventListener('DOMContentLoaded', function() {
    var style = document.createElement('style');
    style.textContent = `
        .sllist-field-warning {
            display: block;
            margin-top: 5px;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .sllist-field-warning.error {
            background-color: #fcf2f2;
            border: 1px solid #dc3232;
            color: #dc3232;
        }
        
        .sllist-field-warning.warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
        }
        
        .form-table input.error,
        .form-table textarea.error {
            border-color: #dc3232;
            box-shadow: 0 0 2px rgba(220, 50, 50, 0.3);
        }
        
        .sllist-field-row.disabled {
            opacity: 0.6;
        }
        
        .sllist-field-row.disabled .sllist-field-label input {
            background-color: #f6f7f7;
            color: #646970;
        }
    `;
    document.head.appendChild(style);
});
