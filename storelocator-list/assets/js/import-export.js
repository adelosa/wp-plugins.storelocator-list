/**
 * Import/Export JavaScript
 * 
 * @package StoreLocator-List
 * @since 0.2.1
 */

(function($) {
    'use strict';
    
    var ImportExport = {
        
        /**
         * Initialize functionality
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Export form submission
            $('#sllist-export-form').on('submit', this.handleExport.bind(this));
            
            // Import form submission
            $('#sllist-import-form').on('submit', this.handleImport.bind(this));
            
            // File input change
            $('#import_file').on('change', this.handleFileSelect.bind(this));
        },
        
        /**
         * Handle export form submission
         */
        handleExport: function(e) {
            e.preventDefault();
            
            var $form = $(e.target);
            var $button = $form.find('button[type="submit"]');
            var originalText = $button.text();
            
            // Show loading state
            $button.prop('disabled', true).text(sllist_import_export.strings.exporting);
            this.showStatus('export-status', 'info', 'Preparing export...');
            
            // Prepare form data
            var formData = new FormData($form[0]);
            formData.append('action', 'sllist_export_stores');
            formData.append('nonce', sllist_import_export.nonce);
            
            $.ajax({
                url: sllist_import_export.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var message = 'Export completed! ' + response.data.count + ' stores exported.';
                        var downloadLink = '<br><a href="' + response.data.download_url + '" class="sllist-download-link" download="' + response.data.filename + '">Download ' + response.data.filename + '</a>';
                        ImportExport.showStatus('export-status', 'success', message + downloadLink);
                    } else {
                        ImportExport.showStatus('export-status', 'error', response.data);
                    }
                },
                error: function() {
                    ImportExport.showStatus('export-status', 'error', sllist_import_export.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        /**
         * Handle import form submission
         */
        handleImport: function(e) {
            e.preventDefault();
            
            var $form = $(e.target);
            var $button = $form.find('button[type="submit"]');
            var originalText = $button.text();
            var importMode = $form.find('input[name="import_mode"]:checked').val();
            
            // Confirmation for destructive operations
            if (importMode === 'replace') {
                if (!confirm('This will DELETE ALL existing stores and replace them with the imported data. This cannot be undone. Are you sure?')) {
                    return;
                }
            } else if (!confirm(sllist_import_export.strings.confirm_import)) {
                return;
            }
            
            // Check if file is selected
            var fileInput = $form.find('#import_file')[0];
            if (!fileInput.files || !fileInput.files[0]) {
                this.showStatus('import-status', 'error', 'Please select a file to import.');
                return;
            }
            
            // Show loading state
            $button.prop('disabled', true).text(sllist_import_export.strings.importing);
            this.showStatus('import-status', 'info', 'Processing import...');
            
            // Prepare form data
            var formData = new FormData($form[0]);
            formData.append('action', 'sllist_import_stores');
            formData.append('nonce', sllist_import_export.nonce);
            
            $.ajax({
                url: sllist_import_export.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var message = response.data.message;
                        
                        // Show detailed results
                        if (response.data.stats.errors.length > 0) {
                            message += '<div class="sllist-error-list">';
                            message += '<h4>Errors encountered:</h4>';
                            message += '<ul>';
                            response.data.stats.errors.forEach(function(error) {
                                message += '<li>' + error + '</li>';
                            });
                            message += '</ul>';
                            message += '</div>';
                        }
                        
                        ImportExport.showStatus('import-status', 'success', message);
                        
                        // Refresh page after successful import to update statistics
                        setTimeout(function() {
                            window.location.reload();
                        }, 3000);
                        
                    } else {
                        ImportExport.showStatus('import-status', 'error', response.data);
                    }
                },
                error: function() {
                    ImportExport.showStatus('import-status', 'error', sllist_import_export.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        /**
         * Handle file selection
         */
        handleFileSelect: function(e) {
            var file = e.target.files[0];
            if (file) {
                var fileName = file.name;
                var fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
                var fileType = file.type;
                
                // Validate file type
                var allowedTypes = [
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                    'application/vnd.ms-excel' // .xls
                ];
                
                if (!allowedTypes.includes(fileType) && !fileName.match(/\.(xlsx|xls)$/i)) {
                    this.showStatus('import-status', 'error', 'Please select a valid Excel file (.xlsx or .xls).');
                    e.target.value = '';
                    return;
                }
                
                // Check file size (limit to 10MB)
                if (file.size > 10 * 1024 * 1024) {
                    this.showStatus('import-status', 'error', 'File size is too large. Please select a file smaller than 10MB.');
                    e.target.value = '';
                    return;
                }
                
                this.showStatus('import-status', 'info', 'File selected: ' + fileName + ' (' + fileSize + ' MB)');
            }
        },
        
        /**
         * Show status message
         */
        showStatus: function(elementId, type, message) {
            var $element = $('#' + elementId);
            $element.removeClass('success error info')
                    .addClass(type)
                    .html(message)
                    .show();
            
            // Scroll to status message
            $('html, body').animate({
                scrollTop: $element.offset().top - 100
            }, 300);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        ImportExport.init();
    });
    
    // Make available globally for debugging
    window.ImportExport = ImportExport;
    
})(jQuery);
