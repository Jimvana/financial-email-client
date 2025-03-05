/**
 * Financial Email Client JavaScript
 */
jQuery(document).ready(function($) {
    // Toggle other IMAP settings when provider is 'other'
    $('#fec-provider').on('change', function() {
        if ($(this).val() === 'other') {
            $('.fec-other-settings').show();
        } else {
            $('.fec-other-settings').hide();
        }
    });
    
    // Handle email account connection form submission
    $('#fec-connect-email-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var messageContainer = $('#fec-connection-message');
        
        // Clear any previous messages
        messageContainer.removeClass('fec-error fec-success').hide();
        
        // Disable submit button to prevent multiple submissions
        form.find('button[type="submit"]').prop('disabled', true).text(fecAjax.connecting_text);
        
        // Collect form data
        var formData = {
            action: 'connect_email_account',
            nonce: fecAjax.nonce,
            email: $('#fec-email').val(),
            provider: $('#fec-provider').val(),
            password: $('#fec-password').val()
        };
        
        // Add additional fields if provider is 'other'
        if ($('#fec-provider').val() === 'other') {
            formData.imap_server = $('#fec-imap-server').val();
            formData.imap_port = $('#fec-imap-port').val();
            formData.imap_encryption = $('#fec-imap-encryption').val();
        }
        
        // Send AJAX request
        $.post(fecAjax.ajax_url, formData, function(response) {
            if (response.success) {
                messageContainer.addClass('fec-success').text(response.data.message).show();
                
                // Reload page after successful connection
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                messageContainer.addClass('fec-error').text(response.data.message).show();
            }
        }).fail(function() {
            messageContainer.addClass('fec-error').text(fecAjax.connection_failed_text).show();
        }).always(function() {
            // Re-enable submit button
            form.find('button[type="submit"]').prop('disabled', false).text(fecAjax.connect_text);
        });
    });
    
    // Initialize email client functionality if present
    if ($('.fec-email-client').length > 0) {
        var currentFolder = 'INBOX';
        var currentPage = 1;
        var currentSearch = '';
        
        // Load initial emails
        loadEmails();
        
        // Handle folder selection
        $('.fec-folder, .fec-virtual-folder').on('click', function() {
            $('.fec-folder, .fec-virtual-folder').removeClass('active');
            $(this).addClass('active');
            
            if ($(this).hasClass('fec-folder')) {
                currentFolder = $(this).data('folder');
            } else {
                // Handle virtual folders (financial categories)
                currentFolder = 'virtual_' + $(this).data('type');
            }
            
            currentPage = 1;
            loadEmails();
        });
        
        // Handle refresh button
        $('#fec-refresh').on('click', function() {
            loadEmails();
        });
        
        // Handle search
        $('#fec-search-button').on('click', function() {
            currentSearch = $('#fec-search').val();
            currentPage = 1;
            loadEmails();
        });
        
        // Handle search on Enter key
        $('#fec-search').on('keypress', function(e) {
            if (e.which === 13) {
                currentSearch = $(this).val();
                currentPage = 1;
                loadEmails();
            }
        });
        
        // Load emails from server
        function loadEmails() {
            console.log('Loading emails from folder:', currentFolder);
            $('#fec-email-list').html('<div class="fec-loading">' + fecAjax.loading_emails_text + '</div>');
            
            // Add a timeout indicator
            var loadingTimeout = setTimeout(function() {
                if ($('.fec-loading').length > 0) {
                    $('.fec-loading').html('Loading emails... This is taking longer than expected. If this continues, please refresh the page.');
                }
            }, 10000); // 10 second timeout
            
            $.post(fecAjax.ajax_url, {
                action: 'fetch_emails',
                nonce: fecAjax.nonce,
                folder: currentFolder,
                page: currentPage,
                search: currentSearch
            }, function(response) {
                clearTimeout(loadingTimeout);
                console.log('Received response:', response);
                
                try {
                    if (response.success) {
                        if (response.data && response.data.messages) {
                            displayEmails(response.data);
                        } else {
                            console.error('Response data missing messages array');
                            $('#fec-email-list').html('<div class="fec-error">Error: Invalid response format</div>');
                        }
                    } else {
                        var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                        console.error('Error loading emails:', errorMsg);
                        $('#fec-email-list').html('<div class="fec-error">' + errorMsg + '</div>');
                    }
                } catch (e) {
                    console.error('Error processing response:', e);
                    $('#fec-email-list').html('<div class="fec-error">Error processing response</div>');
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                clearTimeout(loadingTimeout);
                console.error('AJAX request failed:', textStatus, errorThrown);
                $('#fec-email-list').html('<div class="fec-error">AJAX Error: ' + textStatus + '</div>');
            });
        }
            
        // Display emails in the list
        function displayEmails(data) {
            var html = '';
            
            if (data.messages.length === 0) {
                html = '<div class="fec-no-emails">' + fecAjax.no_emails_text + '</div>';
            } else {
                // Add emails
                data.messages.forEach(function(email) {
                    var template = $('#fec-email-list-template').html();
                    template = template.replace('{seen-class}', email.flags.includes('seen') ? 'fec-read' : 'fec-unread');
                    template = template.replace('{uid}', email.uid);
                    template = template.replace('{sender}', email.from_name || email.from);
                    template = template.replace('{subject}', email.subject);
                    template = template.replace('{preview}', email.preview);
                    template = template.replace('{date}', formatDate(email.date));
                    template = template.replace('{has-financial}', email.financial_data ? 'block' : 'none');
                    
                    html += template;
                });
                
                // Add pagination
                html += '<div class="fec-pagination">';
                
                if (data.page > 1) {
                    html += '<button class="fec-prev-page" data-page="' + (data.page - 1) + '">' + fecAjax.prev_text + '</button>';
                }
                
                html += '<span class="fec-page-info">' + fecAjax.page_text + ' ' + data.page + ' ' + fecAjax.of_text + ' ' + data.total_pages + '</span>';
                
                if (data.page < data.total_pages) {
                    html += '<button class="fec-next-page" data-page="' + (data.page + 1) + '">' + fecAjax.next_text + '</button>';
                }
                
                html += '</div>';
            }
            
            $('#fec-email-list').html(html);
            
            // Add click handlers for emails and pagination
            $('.fec-email-item').on('click', function() {
                $('.fec-email-item').removeClass('selected');
                $(this).addClass('selected');
                
                var uid = $(this).data('uid');
                loadEmail(uid);
            });
            
            $('.fec-prev-page, .fec-next-page').on('click', function() {
                currentPage = $(this).data('page');
                loadEmails();
            });
        }
        
        // Load an email by UID
        function loadEmail(uid) {
            $('#fec-email-view').html('<div class="fec-loading">' + fecAjax.loading_email_text + '</div>');
            
            $.post(fecAjax.ajax_url, {
                action: 'fetch_email',
                nonce: fecAjax.nonce,
                uid: uid
            }, function(response) {
                if (response.success) {
                    displayEmail(response.data);
                    
                    // Mark as read in UI
                    $('.fec-email-item[data-uid="' + uid + '"]').removeClass('fec-unread').addClass('fec-read');
                } else {
                    $('#fec-email-view').html('<div class="fec-error">' + response.data.message + '</div>');
                }
            }).fail(function() {
                $('#fec-email-view').html('<div class="fec-error">' + fecAjax.failed_load_email_text + '</div>');
            });
        }
        
        // Display email content
        function displayEmail(email) {
            var template = $('#fec-email-view-template').html();
            
            // Replace template placeholders
            template = template.replace('{subject}', email.subject);
            template = template.replace('{from}', email.from_name ? email.from_name + ' <' + email.from + '>' : email.from);
            
            // Format to addresses
            var toAddresses = '';
            if (email.to && email.to.length > 0) {
                email.to.forEach(function(recipient, index) {
                    toAddresses += recipient.name ? recipient.name + ' <' + recipient.email + '>' : recipient.email;
                    if (index < email.to.length - 1) {
                        toAddresses += ', ';
                    }
                });
            }
            template = template.replace('{to}', toAddresses);
            
            template = template.replace('{date}', formatFullDate(email.date));
            template = template.replace('{body}', email.body);
            
            // Handle attachments
            template = template.replace('{has-attachments}', email.attachments && email.attachments.length > 0 ? 'block' : 'none');
            
            var attachmentsHtml = '';
            if (email.attachments && email.attachments.length > 0) {
                email.attachments.forEach(function(attachment) {
                    attachmentsHtml += '<div class="fec-attachment-item">';
                    attachmentsHtml += '<span class="fec-attachment-icon"></span>';
                    attachmentsHtml += '<span class="fec-attachment-name">' + attachment.name + '</span>';
                    attachmentsHtml += '<span class="fec-attachment-size">(' + formatSize(attachment.size) + ')</span>';
                    attachmentsHtml += '<a href="#" class="fec-download-attachment" data-uid="' + email.uid + '" data-part="' + attachment.part_number + '">' + fecAjax.download_text + '</a>';
                    attachmentsHtml += '</div>';
                });
            }
            template = template.replace('{attachments}', attachmentsHtml);
            
            $('#fec-email-view').html(template);
            
            // Add attachment download handler
            $('.fec-download-attachment').on('click', function(e) {
                e.preventDefault();
                
                var uid = $(this).data('uid');
                var part = $(this).data('part');
                
                window.location.href = fecAjax.ajax_url + '?action=download_attachment&nonce=' + fecAjax.nonce + '&uid=' + uid + '&part=' + part;
            });
        }
        
        // Helper for formatting dates
        function formatDate(dateString) {
            var date = new Date(dateString);
            var now = new Date();
            
            // If today, show time
            if (date.toDateString() === now.toDateString()) {
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
            
            // If this year, show month and day
            if (date.getFullYear() === now.getFullYear()) {
                return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
            }
            
            // Otherwise show full date
            return date.toLocaleDateString([], { year: 'numeric', month: 'short', day: 'numeric' });
        }
        
        // Helper for formatting full dates
        function formatFullDate(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) + ' ' +
                date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        
        // Helper for formatting file sizes
        function formatSize(bytes) {
            if (bytes < 1024) {
                return bytes + ' B';
            } else if (bytes < 1024 * 1024) {
                return (bytes / 1024).toFixed(1) + ' KB';
            } else {
                return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
            }
        }
    }
});