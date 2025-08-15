/**
 * Store Search JavaScript
 * Handles AJAX search functionality for the store manager page
 * Version: 0.2.0
 */

(function($) {
    'use strict';

    var StoreSearch = {
        
        currentPage: 1,
        currentSearchTerm: '',
        currentSearchFields: [],
        isSearching: false,
        
        /**
         * Initialize store search functionality
         */
        init: function() {
            this.bindEvents();
            this.focusSearchInput();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Search form submission
            $('#sllist-store-search-form').on('submit', this.handleSearchSubmit.bind(this));
            
            // Pagination clicks
            $(document).on('click', '.sllist-page-btn', this.handlePageClick.bind(this));
            
            // Request access button clicks
            $(document).on('click', '.sllist-request-access-btn', this.handleAccessRequest.bind(this));
            
            // Search input keyup for live search (debounced)
            var searchTimeout;
            $('#search_term').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    if ($('#search_term').val().length >= 3) {
                        $('#sllist-store-search-form').trigger('submit');
                    }
                }, 500);
            });
            
            // Search field checkboxes
            $('input[name="search_fields[]"]').on('change', function() {
                // Ensure at least one checkbox is checked
                if ($('input[name="search_fields[]"]:checked').length === 0) {
                    $(this).prop('checked', true);
                    StoreSearch.showMessage('Please select at least one search field.', 'warning');
                }
            });
        },
        
        /**
         * Focus on search input
         */
        focusSearchInput: function() {
            $('#search_term').focus();
        },
        
        /**
         * Handle search form submission
         */
        handleSearchSubmit: function(e) {
            e.preventDefault();
            
            if (this.isSearching) {
                return;
            }
            
            var searchTerm = $('#search_term').val().trim();
            var searchFields = [];
            
            $('input[name="search_fields[]"]:checked').each(function() {
                searchFields.push($(this).val());
            });
            
            // Validation
            if (!searchTerm) {
                this.showMessage(sllist_search_ajax.messages.no_search_term, 'error');
                this.focusSearchInput();
                return;
            }
            
            if (searchFields.length === 0) {
                this.showMessage(sllist_search_ajax.messages.select_fields, 'error');
                return;
            }
            
            // Store current search parameters
            this.currentSearchTerm = searchTerm;
            this.currentSearchFields = searchFields;
            this.currentPage = 1;
            
            this.performSearch();
        },
        
        /**
         * Handle pagination click
         */
        handlePageClick: function(e) {
            e.preventDefault();
            
            if (this.isSearching) {
                return;
            }
            
            var page = parseInt($(e.target).data('page'));
            if (page && page !== this.currentPage) {
                this.currentPage = page;
                this.performSearch();
            }
        },
        
        /**
         * Perform AJAX search
         */
        performSearch: function() {
            this.isSearching = true;
            this.showLoading();
            
            var data = {
                action: 'sllist_search_stores',
                nonce: sllist_search_ajax.nonce,
                search_term: this.currentSearchTerm,
                search_fields: this.currentSearchFields,
                page: this.currentPage
            };
            
            $.ajax({
                url: sllist_search_ajax.ajax_url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: this.handleSearchSuccess.bind(this),
                error: this.handleSearchError.bind(this),
                complete: this.handleSearchComplete.bind(this)
            });
        },
        
        /**
         * Handle successful search response
         */
        handleSearchSuccess: function(response) {
            if (response.success) {
                $('#sllist-results-container').html(response.data.html);
                $('#sllist-pagination').html(response.data.pagination);
                $('#sllist-search-results').show();
                
                // Show results count
                var message = 'Found ' + response.data.total + ' store(s) matching your search.';
                this.showMessage(message, 'success');
                
                // Scroll to results
                $('html, body').animate({
                    scrollTop: $('#sllist-search-results').offset().top - 50
                }, 300);
                
            } else {
                this.showMessage(response.data, 'error');
                $('#sllist-search-results').hide();
            }
        },
        
        /**
         * Handle search error
         */
        handleSearchError: function(xhr, status, error) {
            console.error('Search error:', error);
            this.showMessage(sllist_search_ajax.messages.error, 'error');
            $('#sllist-search-results').hide();
        },
        
        /**
         * Handle search completion
         */
        handleSearchComplete: function() {
            this.isSearching = false;
            this.hideLoading();
        },
        
        /**
         * Handle access request button click
         */
        handleAccessRequest: function(e) {
            e.preventDefault();
            
            var $button = $(e.target);
            var storeId = $button.data('store-id');
            var storeName = $button.data('store-name');
            
            if (!storeId) {
                return;
            }
            
            // Show confirmation dialog
            var message = 'Request access to update "' + storeName + '"?\n\n' +
                         'You will receive an email with a secure link and password to update your store details.';
            
            if (confirm(message)) {
                this.requestStoreAccess(storeId, $button);
            }
        },
        
        /**
         * Request store access via AJAX
         */
        requestStoreAccess: function(storeId, $button) {
            var originalText = $button.text();
            $button.prop('disabled', true).text('Requesting...');
            
            var data = {
                action: 'sllist_request_store_access',
                nonce: sllist_search_ajax.nonce,
                store_id: storeId
            };
            
            $.ajax({
                url: sllist_search_ajax.ajax_url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        StoreSearch.showMessage(response.data, 'success');
                        
                        // Replace button with success message
                        var $parent = $button.closest('.sllist-store-actions');
                        $parent.html('<div class="sllist-message info">Access request sent! Check your email.</div>');
                        
                    } else {
                        StoreSearch.showMessage(response.data, 'error');
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    StoreSearch.showMessage('An error occurred while requesting access. Please try again.', 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        /**
         * Show loading state
         */
        showLoading: function() {
            $('#sllist-search-results').addClass('sllist-loading');
            $('.sllist-button[type="submit"]').prop('disabled', true).text(sllist_search_ajax.messages.searching);
        },
        
        /**
         * Hide loading state
         */
        hideLoading: function() {
            $('#sllist-search-results').removeClass('sllist-loading');
            $('.sllist-button[type="submit"]').prop('disabled', false).text('Search Stores');
        },
        
        /**
         * Show message to user
         */
        showMessage: function(message, type) {
            type = type || 'info';
            var $container = $('#sllist-search-messages');
            
            var messageHtml = '<div class="sllist-message ' + type + '">' + message + '</div>';
            $container.html(messageHtml);
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $container.offset().top - 100
            }, 200);
            
            // Auto-hide success and info messages after 5 seconds
            if (type === 'success' || type === 'info') {
                setTimeout(function() {
                    $container.find('.sllist-message.' + type).fadeOut();
                }, 5000);
            }
        },
        
        /**
         * Clear messages
         */
        clearMessages: function() {
            $('#sllist-search-messages').empty();
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize on store manager page
        if ($('#sllist-store-search-form').length) {
            StoreSearch.init();
        }
    });
    
    // Make StoreSearch object globally available for debugging
    window.StoreSearch = StoreSearch;
    
})(jQuery);
