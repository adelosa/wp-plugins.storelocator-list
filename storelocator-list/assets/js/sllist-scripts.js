/**
 * StoreLocator-List Plugin JavaScript
 * Version: 0.2.0
 */

(function($) {
    'use strict';

    // Plugin object
    var SLList = {
        
        /**
         * Initialize plugin functionality
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Future functionality will be added here
            // For example: store search, form submissions, etc.
        },
        
        /**
         * Show loading state
         */
        showLoading: function(element) {
            if (element) {
                element.addClass('sllist-loading');
                element.append('<div class="sllist-spinner"></div>');
            }
        },
        
        /**
         * Hide loading state
         */
        hideLoading: function(element) {
            if (element) {
                element.removeClass('sllist-loading');
                element.find('.sllist-spinner').remove();
            }
        },
        
        /**
         * Display message to user
         */
        showMessage: function(message, type, container) {
            type = type || 'info';
            container = container || $('.sllist-messages');
            
            var messageHtml = '<div class="sllist-message ' + type + '">' + message + '</div>';
            
            if (container.length) {
                container.html(messageHtml);
            } else {
                // Fallback: show as alert
                alert(message);
            }
            
            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    container.find('.sllist-message.success').fadeOut();
                }, 5000);
            }
        },
        
        /**
         * AJAX helper function
         */
        ajaxRequest: function(action, data, callback) {
            data = data || {};
            data.action = 'sllist_' + action;
            data.nonce = sllist_ajax.nonce;
            
            $.ajax({
                url: sllist_ajax.ajax_url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (callback && typeof callback === 'function') {
                        callback(response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    if (callback && typeof callback === 'function') {
                        callback({
                            success: false,
                            data: 'An error occurred. Please try again.'
                        });
                    }
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        SLList.init();
    });
    
    // Make SLList object globally available
    window.SLList = SLList;
    
})(jQuery);
