(function($) {
    'use strict';

    // Quick Tools Admin Object
    const QuickToolsAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initSearch();
            this.initImportExport();
            this.initCPTSelection();
        },

        bindEvents: function() {
            // Modal close events
            $(document).on('click', '.qt-modal-close, .qt-modal', function(e) {
                if (e.target === this) {
                    $('.qt-modal').hide();
                }
            });

            // Search trigger in dashboard widgets
            $(document).on('click', '.qt-search-trigger', function(e) {
                e.preventDefault();
                QuickToolsAdmin.showSearchModal();
            });

            // Tab navigation (if needed for future enhancements)
            $('.qt-nav-tabs .nav-tab').on('click', function(e) {
                const href = $(this).attr('href');
                if (href && href.includes('tab=')) {
                    // Let default behavior handle tab switching
                    return true;
                }
            });

            // CPT card selection
            $(document).on('change', '.qt-cpt-card input[type="checkbox"]', function() {
                const $card = $(this).closest('.qt-cpt-card');
                if ($(this).is(':checked')) {
                    $card.addClass('selected');
                } else {
                    $card.removeClass('selected');
                }
            });

            // Prevent card click from affecting checkbox
            $(document).on('click', '.qt-cpt-card', function(e) {
                if (e.target.type !== 'checkbox' && !$(e.target).is('a, button')) {
                    const $checkbox = $(this).find('input[type="checkbox"]');
                    $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
                }
            });
        },

        initSearch: function() {
            $('#qt-search-button').on('click', this.performSearch);
            $('#qt-search-input').on('keypress', function(e) {
                if (e.which === 13) {
                    QuickToolsAdmin.performSearch();
                }
            });
        },

        initImportExport: function() {
            // Export buttons
            $('.qt-export-btn').on('click', function() {
                const category = $(this).data('category');
                QuickToolsAdmin.exportDocumentation(category);
            });

            // Import file selection
            $('#qt-select-file-btn').on('click', function() {
                $('#qt-import-file').click();
            });

            $('#qt-import-file').on('change', function() {
                const file = this.files[0];
                if (file) {
                    QuickToolsAdmin.previewImport(file);
                }
            });

            // Import actions
            $('#qt-import-btn').on('click', this.importDocumentation);
            $('#qt-cancel-import-btn').on('click', this.cancelImport);
            $('.qt-clear-file').on('click', this.clearImportFile);
        },

        initCPTSelection: function() {
            // Initialize already selected CPT cards
            $('.qt-cpt-card input[type="checkbox"]:checked').each(function() {
                $(this).closest('.qt-cpt-card').addClass('selected');
            });
        },

        showSearchModal: function() {
            $('#qt-search-modal').show();
            $('#qt-search-input').focus();
        },

        performSearch: function() {
            const searchTerm = $('#qt-search-input').val().trim();
            
            if (searchTerm.length < 2) {
                QuickToolsAdmin.showSearchError(quickToolsAjax.strings.error);
                return;
            }

            const $results = $('#qt-search-results');
            $results.html('<div class="qt-loading-search">' + quickToolsAjax.strings.searching + '</div>');

            $.ajax({
                url: quickToolsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'qt_search_documentation',
                    nonce: quickToolsAjax.nonce,
                    search_term: searchTerm
                },
                success: function(response) {
                    if (response.success) {
                        QuickToolsAdmin.displaySearchResults(response.data);
                    } else {
                        QuickToolsAdmin.showSearchError(response.data || quickToolsAjax.strings.error);
                    }
                },
                error: function() {
                    QuickToolsAdmin.showSearchError(quickToolsAjax.strings.error);
                }
            });
        },

        // Helper function to escape HTML
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        displaySearchResults: function(results) {
            const $results = $('#qt-search-results');
            
            if (results.length === 0) {
                $results.html('<p>' + quickToolsAjax.strings.no_results + '</p>');
                return;
            }

            let html = '';
            results.forEach(function(result) {
                html += '<div class="qt-search-result">';
                html += '<h4><a href="' + QuickToolsAdmin.escapeHtml(result.view_url) + '">' + QuickToolsAdmin.escapeHtml(result.title) + '</a></h4>';
                html += '<p>' + QuickToolsAdmin.escapeHtml(result.excerpt) + '</p>';
                
                if (result.categories.length > 0) {
                    html += '<div class="qt-search-categories">';
                    result.categories.forEach(function(category) {
                        html += '<span class="qt-search-category">' + QuickToolsAdmin.escapeHtml(category) + '</span>';
                    });
                    html += '</div>';
                }
                
                html += '<div class="qt-search-actions">';
                html += '<a href="' + QuickToolsAdmin.escapeHtml(result.view_url) + '" class="button button-small">View</a> ';
                html += '<a href="' + QuickToolsAdmin.escapeHtml(result.edit_url) + '" class="button button-small button-secondary">Edit</a>';
                html += '</div>';
                html += '</div>';
            });

            $results.html(html);
        },

        showSearchError: function(message) {
            $('#qt-search-results').html('<div class="notice notice-error"><p>' + message + '</p></div>');
        },

        exportDocumentation: function(category) {
            QuickToolsAdmin.showProgress('Exporting Documentation...', 'Preparing export file...');

            $.ajax({
                url: quickToolsAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'qt_export_documentation',
                    nonce: quickToolsAjax.nonce,
                    category: category
                },
                success: function(response) {
                    QuickToolsAdmin.hideProgress();
                    
                    if (response.success) {
                        // Create and download file
                        const dataStr = JSON.stringify(response.data.data, null, 2);
                        const dataBlob = new Blob([dataStr], {type: 'application/json'});
                        const url = URL.createObjectURL(dataBlob);
                        
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        URL.revokeObjectURL(url);
                        QuickToolsAdmin.showNotice(quickToolsAjax.strings.export_success, 'success');
                    } else {
                        QuickToolsAdmin.showNotice(response.data || quickToolsAjax.strings.error, 'error');
                    }
                },
                error: function() {
                    QuickToolsAdmin.hideProgress();
                    QuickToolsAdmin.showNotice(quickToolsAjax.strings.error, 'error');
                }
            });
        },

        previewImport: function(file) {
            if (file.type !== 'application/json') {
                alert('Please select a JSON file.');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = JSON.parse(e.target.result);
                    QuickToolsAdmin.showImportPreview(file, data);
                } catch (error) {
                    alert('Invalid JSON file. Please select a valid Quick Tools export file.');
                }
            };
            reader.readAsText(file);
        },

        showImportPreview: function(file, data) {
            $('.qt-filename').text(file.name);
            $('.qt-selected-file').show();
            
            if (data.documentation && Array.isArray(data.documentation)) {
                let previewHtml = '<p><strong>File:</strong> ' + file.name + '</p>';
                previewHtml += '<p><strong>Items to import:</strong> ' + data.documentation.length + '</p>';
                
                if (data.version) {
                    previewHtml += '<p><strong>Export version:</strong> ' + data.version + '</p>';
                }
                
                if (data.export_date) {
                    previewHtml += '<p><strong>Export date:</strong> ' + data.export_date + '</p>';
                }

                // Show categories that will be created
                const categories = new Set();
                data.documentation.forEach(function(doc) {
                    if (doc.categories && Array.isArray(doc.categories)) {
                        doc.categories.forEach(function(cat) {
                            categories.add(cat);
                        });
                    }
                });

                if (categories.size > 0) {
                    previewHtml += '<p><strong>Categories:</strong> ' + Array.from(categories).join(', ') + '</p>';
                }

                $('.qt-import-details').html(previewHtml);
                $('#qt-import-preview').show();
            } else {
                alert('Invalid Quick Tools export file.');
            }
        },

        importDocumentation: function() {
            if (!confirm(quickToolsAjax.strings.confirm_import)) {
                return;
            }

            const fileInput = document.getElementById('qt-import-file');
            if (!fileInput.files.length) {
                alert('Please select a file to import.');
                return;
            }

            QuickToolsAdmin.showProgress('Importing Documentation...', 'Processing import file...');

            const formData = new FormData();
            formData.append('action', 'qt_import_documentation');
            formData.append('nonce', quickToolsAjax.nonce);
            formData.append('import_file', fileInput.files[0]);

            $.ajax({
                url: quickToolsAjax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    QuickToolsAdmin.hideProgress();
                    
                    if (response.success) {
                        let message = quickToolsAjax.strings.import_success;
                        message += ' Imported: ' + response.data.imported + ' items.';
                        
                        if (response.data.errors.length > 0) {
                            message += ' Errors: ' + response.data.errors.length;
                            console.log('Import errors:', response.data.errors);
                        }
                        
                        QuickToolsAdmin.showNotice(message, 'success');
                        QuickToolsAdmin.cancelImport();
                        
                        // Refresh page to show new documentation
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        QuickToolsAdmin.showNotice(response.data || quickToolsAjax.strings.error, 'error');
                    }
                },
                error: function() {
                    QuickToolsAdmin.hideProgress();
                    QuickToolsAdmin.showNotice(quickToolsAjax.strings.error, 'error');
                }
            });
        },

        cancelImport: function() {
            $('#qt-import-file').val('');
            $('.qt-selected-file').hide();
            $('#qt-import-preview').hide();
        },

        clearImportFile: function() {
            QuickToolsAdmin.cancelImport();
        },

        showProgress: function(title, message) {
            $('#qt-progress-title').text(title);
            $('#qt-progress-message').text(message);
            $('#qt-progress-modal').show();
            
            // Simulate progress for better UX
            let progress = 0;
            const interval = setInterval(function() {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                $('.qt-progress-fill').css('width', progress + '%');
            }, 200);
            
            // Store interval ID for cleanup
            $('#qt-progress-modal').data('interval', interval);
        },

        hideProgress: function() {
            const interval = $('#qt-progress-modal').data('interval');
            if (interval) {
                clearInterval(interval);
            }
            
            $('.qt-progress-fill').css('width', '100%');
            setTimeout(function() {
                $('#qt-progress-modal').hide();
                $('.qt-progress-fill').css('width', '0%');
            }, 300);
        },

        showNotice: function(message, type) {
            type = type || 'info';
            
            const notice = $('<div class="notice notice-' + type + ' is-dismissible" style="margin: 15px 0;"><p>' + message + '</p></div>');
            
            // Find the best place to insert the notice
            const $target = $('.qt-tab-content h2').first();
            if ($target.length) {
                $target.after(notice);
            } else {
                $('.qt-tab-content').prepend(notice);
            }
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Make dismissible
            notice.on('click', '.notice-dismiss', function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            });
        }
    };

    // Dashboard Widget Enhancements
    const DashboardEnhancements = {
        
        init: function() {
            if (window.location.pathname.includes('wp-admin/index.php') || 
                window.location.pathname.endsWith('wp-admin/')) {
                this.initDashboardFeatures();
            }
        },

        initDashboardFeatures: function() {
            // Add search functionality to documentation widgets
            this.addSearchToWidgets();
            
            // Enhance CPT widgets
            this.enhanceCPTWidgets();
            
            // Add keyboard shortcuts
            this.addKeyboardShortcuts();
        },

        addSearchToWidgets: function() {
            // This would add inline search to dashboard widgets
            $('.qt-documentation-widget .qt-search-trigger').on('click', function(e) {
                e.preventDefault();
                
                const $widget = $(this).closest('.qt-documentation-widget');
                let $searchBox = $widget.find('.qt-inline-search');
                
                if ($searchBox.length === 0) {
                    $searchBox = $('<div class="qt-inline-search" style="padding: 10px; border-top: 1px solid #f1f1f1;">' +
                        '<input type="text" placeholder="Search documentation..." class="regular-text" style="width: 100%; margin-bottom: 10px;">' +
                        '<div class="qt-inline-results"></div>' +
                        '</div>');
                    $widget.append($searchBox);
                }
                
                $searchBox.toggle();
                $searchBox.find('input').focus();
            });
        },

        enhanceCPTWidgets: function() {
            // Add quick stats updates and other enhancements
            $('.qt-cpt-widget').each(function() {
                const $widget = $(this);
                // Add any real-time features here
            });
        },

        addKeyboardShortcuts: function() {
            $(document).on('keydown', function(e) {
                // Alt + Q = Quick Tools settings
                if (e.altKey && e.key === 'q') {
                    e.preventDefault();
                    window.location.href = window.location.origin + '/wp-admin/admin.php?page=quick-tools';
                }
                
                // Alt + D = Add new documentation
                if (e.altKey && e.key === 'd') {
                    e.preventDefault();
                    window.location.href = window.location.origin + '/wp-admin/post-new.php?post_type=qt_documentation';
                }
            });
        }
    };

    // Initialize everything when document is ready
    $(document).ready(function() {
        QuickToolsAdmin.init();
        DashboardEnhancements.init();
    });

    // Handle page visibility changes for performance
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Pause any ongoing animations or polling
            $('.qt-loading').removeClass('qt-loading');
        }
    });

    // Expose to global scope for external access if needed
    window.QuickToolsAdmin = QuickToolsAdmin;

})(jQuery);
