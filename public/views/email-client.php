<?php
/**
 * Email client template
 * This template is used by the shortcode to display the email client
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="fec-container">
    <?php if (empty($accounts)): ?>
        <!-- No email accounts added yet -->
        <div class="fec-setup-account">
            <h2><?php _e('Connect Your Email Account', 'financial-email-client'); ?></h2>
            <p><?php _e('Connect your email account to start monitoring for financial emails, bills, price increases, and subscription renewals.', 'financial-email-client'); ?></p>
            
            <form id="fec-connect-email-form" class="fec-form">
                <div class="fec-form-group">
                    <label for="fec-email"><?php _e('Email Address', 'financial-email-client'); ?></label>
                    <input type="email" id="fec-email" name="email" required>
                </div>
                
                <div class="fec-form-group">
                    <label for="fec-provider"><?php _e('Email Provider', 'financial-email-client'); ?></label>
                    <select id="fec-provider" name="provider" required>
                        <option value=""><?php _e('Select Provider', 'financial-email-client'); ?></option>
                        <option value="gmail"><?php _e('Gmail', 'financial-email-client'); ?></option>
                        <option value="outlook"><?php _e('Outlook / Hotmail', 'financial-email-client'); ?></option>
                        <option value="yahoo"><?php _e('Yahoo', 'financial-email-client'); ?></option>
                        <option value="other"><?php _e('Other IMAP', 'financial-email-client'); ?></option>
                    </select>
                </div>
                
                <div class="fec-form-group fec-other-settings" style="display: none;">
                    <label for="fec-imap-server"><?php _e('IMAP Server', 'financial-email-client'); ?></label>
                    <input type="text" id="fec-imap-server" name="imap_server">
                    
                    <label for="fec-imap-port"><?php _e('IMAP Port', 'financial-email-client'); ?></label>
                    <input type="number" id="fec-imap-port" name="imap_port" value="993">
                    
                    <label for="fec-imap-encryption"><?php _e('Encryption', 'financial-email-client'); ?></label>
                    <select id="fec-imap-encryption" name="imap_encryption">
                        <option value="ssl"><?php _e('SSL', 'financial-email-client'); ?></option>
                        <option value="tls"><?php _e('TLS', 'financial-email-client'); ?></option>
                        <option value="none"><?php _e('None', 'financial-email-client'); ?></option>
                    </select>
                </div>
                
                <div class="fec-form-group">
                    <label for="fec-password"><?php _e('Password', 'financial-email-client'); ?></label>
                    <input type="password" id="fec-password" name="password" required>
                    <p class="fec-help-text"><?php _e('Your password is securely encrypted and only used to connect to your email provider.', 'financial-email-client'); ?></p>
                </div>
                
                <div class="fec-form-group">
                    <button type="submit" class="fec-button fec-button-primary"><?php _e('Connect Email Account', 'financial-email-client'); ?></button>
                </div>
                
                <div id="fec-connection-message" class="fec-message" style="display: none;"></div>
            </form>
        </div>
    <?php else: ?>
        <!-- Email client interface -->
        <div class="fec-email-client">
            <div class="fec-sidebar">
                <div class="fec-account-info">
                    <span class="fec-email-address"><?php echo esc_html($accounts[0]->email_address); ?></span>
                    <a href="#" class="fec-add-account"><?php _e('Add Another Account', 'financial-email-client'); ?></a>
                </div>
                
                <ul class="fec-folders">
                    <li class="fec-folder active" data-folder="INBOX"><?php _e('Inbox', 'financial-email-client'); ?></li>
                    <li class="fec-folder" data-folder="INBOX.Sent"><?php _e('Sent', 'financial-email-client'); ?></li>
                    <li class="fec-folder" data-folder="INBOX.Drafts"><?php _e('Drafts', 'financial-email-client'); ?></li>
                    <li class="fec-folder" data-folder="INBOX.Trash"><?php _e('Trash', 'financial-email-client'); ?></li>
                </ul>
                
                <div class="fec-financial-folders">
                    <h3><?php _e('Financial', 'financial-email-client'); ?></h3>
                    <ul>
                        <li class="fec-virtual-folder" data-type="bills"><?php _e('Bills & Payments', 'financial-email-client'); ?></li>
                        <li class="fec-virtual-folder" data-type="price_increases"><?php _e('Price Increases', 'financial-email-client'); ?></li>
                        <li class="fec-virtual-folder" data-type="subscriptions"><?php _e('Subscriptions', 'financial-email-client'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="fec-content">
                <div class="fec-toolbar">
                    <div class="fec-search">
                        <input type="text" placeholder="<?php _e('Search emails...', 'financial-email-client'); ?>" id="fec-search">
                        <button id="fec-search-button"><?php _e('Search', 'financial-email-client'); ?></button>
                    </div>
                    
                    <div class="fec-actions">
                        <button id="fec-refresh"><?php _e('Refresh', 'financial-email-client'); ?></button>
                    </div>
                </div>
                
                <div class="fec-email-list-container">
                    <div id="fec-email-list" class="fec-email-list">
                        <div class="fec-loading"><?php _e('Loading emails...', 'financial-email-client'); ?></div>
                    </div>
                    
                    <div id="fec-email-view" class="fec-email-view">
                        <div class="fec-no-email-selected"><?php _e('Select an email to view', 'financial-email-client'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Email list template -->
        <script type="text/template" id="fec-email-list-template">
            <% if (emails.length === 0) { %>
                <div class="fec-no-emails"><?php _e('No emails found', 'financial-email-client'); ?></div>
            <% } else { %>
                <% emails.forEach(function(email) { %>
                    <div class="fec-email-item <%= email.flags.includes('seen') ? 'fec-read' : 'fec-unread' %>" data-uid="<%= email.uid %>">
                        <div class="fec-email-sender"><%= email.from_name || email.from %></div>
                        <div class="fec-email-subject"><%= email.subject %></div>
                        <div class="fec-email-preview"><%= email.preview %></div>
                        <div class="fec-email-date"><%= formatDate(email.date) %></div>
                        <% if (email.financial_data) { %>
                            <div class="fec-financial-indicator" title="<?php _e('Financial information detected', 'financial-email-client'); ?>">$</div>
                        <% } %>
                    </div>
                <% }); %>
                
                <div class="fec-pagination">
                    <% if (page > 1) { %>
                        <button class="fec-prev-page" data-page="<%= page - 1 %>"><?php _e('Previous', 'financial-email-client'); ?></button>
                    <% } %>
                    
                    <span class="fec-page-info"><?php _e('Page', 'financial-email-client'); ?> <%= page %> <?php _e('of', 'financial-email-client'); %> <%= total_pages %></span>
                    
                    <% if (page < total_pages) { %>
                        <button class="fec-next-page" data-page="<%= page + 1 %>"><?php _e('Next', 'financial-email-client'); ?></button>
                    <% } %>
                </div>
            <% } %>
        </script>
        
        <!-- Email view template -->
        <script type="text/template" id="fec-email-view-template">
            <div class="fec-email-header">
                <h2 class="fec-email-subject"><%= email.subject %></h2>
                <div class="fec-email-meta">
                    <div class="fec-email-from">
                        <span class="fec-label"><?php _e('From:', 'financial-email-client'); ?></span>
                        <span class="fec-value"><%= email.from_name ? email.from_name + ' <' + email.from + '>' : email.from %></span>
                    </div>
                    <div class="fec-email-to">
                        <span class="fec-label"><?php _e('To:', 'financial-email-client'); ?></span>
                        <span class="fec-value">
                            <% email.to.forEach(function(recipient, index) { %>
                                <%= recipient.name ? recipient.name + ' <' + recipient.email + '>' : recipient.email %><%= index < email.to.length - 1 ? ', ' : '' %>
                            <% }); %>
                        </span>
                    </div>
                    <div class="fec-email-date">
                        <span class="fec-label"><?php _e('Date:', 'financial-email-client'); ?></span>
                        <span class="fec-value"><%= formatFullDate(email.date) %></span>
                    </div>
                </div>
            </div>
            
            <% if (email.financial_data) { %>
                <div class="fec-financial-insights">
                    <h3><?php _e('Financial Insights', 'financial-email-client'); ?></h3>
                    <div class="fec-insights-list">
                        <% email.financial_data.forEach(function(insight) { %>
                            <div class="fec-insight-item fec-insight-<%= insight.type %>">
                                <div class="fec-insight-icon"></div>
                                <div class="fec-insight-content">
                                    <h4><%= insight.description %></h4>
                                    <% if (insight.amount) { %>
                                        <div class="fec-insight-amount">$<%= insight.amount.toFixed(2) %></div>
                                    <% } %>
                                    <% if (insight.due_date) { %>
                                        <div class="fec-insight-date"><?php _e('Due:', 'financial-email-client'); ?> <%= formatDate(insight.due_date) %></div>
                                    <% } %>
                                    <% if (insight.effective_date) { %>
                                        <div class="fec-insight-date"><?php _e('Effective:', 'financial-email-client'); ?> <%= formatDate(insight.effective_date) %></div>
                                    <% } %>
                                    <% if (insight.renewal_date) { %>
                                        <div class="fec-insight-date"><?php _e('Renewal:', 'financial-email-client'); ?> <%= formatDate(insight.renewal_date) %></div>
                                    <% } %>
                                    <% if (insight.type === 'price_increase' && insight.percentage) { %>
                                        <div class="fec-insight-percentage"><?php _e('Increase:', 'financial-email-client'); ?> <%= insight.percentage %>%</div>
                                    <% } %>
                                    <% if (insight.type === 'price_increase' && insight.old_amount && insight.new_amount) { %>
                                        <div class="fec-insight-change"><?php _e('Change:', 'financial-email-client'); ?> $<%= insight.old_amount.toFixed(2) %> → $<%= insight.new_amount.toFixed(2) %></div>
                                    <% } %>
                                </div>
                            </div>
                        <% }); %>
                    </div>
                </div>
            <% } %>
            
            <div class="fec-email-body">
                <%= email.body %>
            </div>
            
            <% if (email.attachments && email.attachments.length > 0) { %>
                <div class="fec-email-attachments">
                    <h3><?php _e('Attachments', 'financial-email-client'); ?></h3>
                    <div class="fec-attachments-list">
                        <% email.attachments.forEach(function(attachment) { %>
                            <div class="fec-attachment-item">
                                <span class="fec-attachment-icon"></span>
                                <span class="fec-attachment-name"><%= attachment.name %></span>
                                <span class="fec-attachment-size">(<%= formatSize(attachment.size) %>)</span>
                                <a href="#" class="fec-download-attachment" data-uid="<%= email.uid %>" data-part="<%= attachment.part_number %>"><?php _e('Download', 'financial-email-client'); ?></a>
                            </div>
                        <% }); %>
                    </div>
                </div>
            <% } %>
        </script>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle other IMAP settings when provider is "other"
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
        form.find('button[type="submit"]').prop('disabled', true).text('<?php _e('Connecting...', 'financial-email-client'); ?>');
        
        // Collect form data
        var formData = {
            action: 'connect_email_account',
            nonce: fecAjax.nonce,
            email: $('#fec-email').val(),
            provider: $('#fec-provider').val(),
            password: $('#fec-password').val()
        };
        
        // Add additional fields if provider is "other"
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
            messageContainer.addClass('fec-error').text('<?php _e('Connection failed. Please try again.', 'financial-email-client'); ?>').show();
        }).always(function() {
            // Re-enable submit button
            form.find('button[type="submit"]').prop('disabled', false).text('<?php _e('Connect Email Account', 'financial-email-client'); ?>');
        });
    });
    
    <?php if (!empty($accounts)): ?>
    // Initialize email client
    var currentFolder = 'INBOX';
    var currentPage = 1;
    var currentSearch = '';
    
    // Helper function to format dates
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
    
    // Helper function to format full dates
    function formatFullDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) + ' ' +
               date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    // Helper function to format file sizes
    function formatSize(bytes) {
        if (bytes < 1024) {
            return bytes + ' B';
        } else if (bytes < 1024 * 1024) {
            return (bytes / 1024).toFixed(1) + ' KB';
        } else {
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }
    }
    
    // Load emails from server
    function loadEmails() {
        $('#fec-email-list').html('<div class="fec-loading"><?php _e('Loading emails...', 'financial-email-client'); ?></div>');
        
        $.post(fecAjax.ajax_url, {
            action: 'fetch_emails',
            nonce: fecAjax.nonce,
            folder: currentFolder,
            page: currentPage,
            search: currentSearch
        }, function(response) {
            if (response.success) {
                // Use template to render email list
                var template = _.template($('#fec-email-list-template').html());
                $('#fec-email-list').html(template({
                    emails: response.data.messages,
                    page: response.data.page,
                    total_pages: response.data.total_pages,
                    formatDate: formatDate
                }));
            } else {
                $('#fec-email-list').html('<div class="fec-error">' + response.data.message + '</div>');
            }
        }).fail(function() {
            $('#fec-email-list').html('<div class="fec-error"><?php _e('Failed to load emails. Please try again.', 'financial-email-client'); ?></div>');
        });
    }
    
    // Load an email by UID
    function loadEmail(uid) {
        $('#fec-email-view').html('<div class="fec-loading"><?php _e('Loading email...', 'financial-email-client'); ?></div>');
        
        $.post(fecAjax.ajax_url, {
            action: 'fetch_email',
            nonce: fecAjax.nonce,
            uid: uid
        }, function(response) {
            if (response.success) {
                // Use template to render email view
                var template = _.template($('#fec-email-view-template').html());
                $('#fec-email-view').html(template({
                    email: response.data,
                    formatDate: formatDate,
                    formatFullDate: formatFullDate,
                    formatSize: formatSize
                }));
                
                // Mark as read in UI
                $('.fec-email-item[data-uid="' + uid + '"]').removeClass('fec-unread').addClass('fec-read');
            } else {
                $('#fec-email-view').html('<div class="fec-error">' + response.data.message + '</div>');
            }
        }).fail(function() {
            $('#fec-email-view').html('<div class="fec-error"><?php _e('Failed to load email. Please try again.', 'financial-email-client'); ?></div>');
        });
    }
    
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
    
    // Handle email selection
    $(document).on('click', '.fec-email-item', function() {
        $('.fec-email-item').removeClass('selected');
        $(this).addClass('selected');
        
        var uid = $(this).data('uid');
        loadEmail(uid);
    });
    
    // Handle pagination
    $(document).on('click', '.fec-prev-page, .fec-next-page', function() {
        currentPage = $(this).data('page');
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
    
    // Handle attachment download
    $(document).on('click', '.fec-download-attachment', function(e) {
        e.preventDefault();
        
        var uid = $(this).data('uid');
        var part = $(this).data('part');
        
        window.location.href = fecAjax.ajax_url + '?action=download_attachment&nonce=' + fecAjax.nonce + '&uid=' + uid + '&part=' + part;
    });
    <?php endif; ?>
});
</script>