<?php
/**
 * Plugin Name: Financial Email Client
 * Plugin URI: https://yourwebsite.com/financial-email-client
 * Description: Email client with financial monitoring capabilities to help users manage bills and finances.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: financial-email-client
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Financial_Email_Client {
    /**
     * Constructor
     */
    public function __construct() {
        // Define constants
        $this->define_constants();
        
        // Include required files
        $this->includes();
        
        // Setup hooks
        $this->setup_hooks();
        
        // Register shortcodes
        $this->register_shortcodes();
        
        // Register widgets
        add_action('widgets_init', array($this, 'register_widgets'));
    }
    
    /**
     * Define constants
     */
    private function define_constants() {
        define('FEC_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('FEC_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('FEC_VERSION', '1.0.0');
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Core functionality
        require_once FEC_PLUGIN_DIR . 'includes/class-email-connector.php';
        require_once FEC_PLUGIN_DIR . 'includes/class-email-parser.php';
        require_once FEC_PLUGIN_DIR . 'includes/class-financial-analyzer.php';
        
        // Admin functionality
        if (is_admin()) {
            require_once FEC_PLUGIN_DIR . 'admin/class-admin-settings.php';
        }
        
        // Front-end components
        require_once FEC_PLUGIN_DIR . 'public/class-email-client-ui.php';
        require_once FEC_PLUGIN_DIR . 'public/class-financial-dashboard.php';
        
        // Widgets
        require_once FEC_PLUGIN_DIR . 'widgets/class-bill-alerts-widget.php';
        require_once FEC_PLUGIN_DIR . 'widgets/class-financial-summary-widget.php';
    }
    
    /**
     * Setup hooks
     */
    private function setup_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add menu items
        add_action('admin_menu', array($this, 'admin_menu'));

        // AJAX handlers
        add_action('wp_ajax_connect_email_account', array($this, 'handle_email_connection'));
        add_action('wp_ajax_fetch_emails', array($this, 'fetch_user_emails'));
        add_action('wp_ajax_analyze_email', array($this, 'analyze_email_content'));
    }
    
    /**
     * Register shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('email_financial_client', array($this, 'render_email_client'));
        add_shortcode('financial_insights', array($this, 'render_financial_insights'));
    }
    
    /**
     * Register widgets
     */
    public function register_widgets() {
        register_widget('Financial_Summary_Widget');
        register_widget('Bill_Alerts_Widget');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create necessary database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule cron jobs for background processing
        $this->schedule_events();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        $this->clear_scheduled_events();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for storing email account connections
        $table_email_accounts = $wpdb->prefix . 'fec_email_accounts';
        $sql_email_accounts = "CREATE TABLE $table_email_accounts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            email_address varchar(100) NOT NULL,
            provider varchar(50) NOT NULL,
            auth_token text NOT NULL,
            refresh_token text,
            token_expiry datetime,
            server_settings text,
            last_checked datetime,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Table for storing financial insights
        $table_financial_insights = $wpdb->prefix . 'fec_financial_insights';
        $sql_financial_insights = "CREATE TABLE $table_financial_insights (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            email_id varchar(255) NOT NULL,
            insight_type varchar(50) NOT NULL,
            description text NOT NULL,
            amount decimal(10,2),
            due_date date,
            status varchar(20),
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_email_accounts);
        dbDelta($sql_financial_insights);
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'encryption_key' => wp_generate_password(64, true, true),
            'scan_frequency' => 'hourly',
            'providers_enabled' => array('gmail', 'outlook', 'yahoo'),
            'analysis_types' => array('price_increase', 'bill_due', 'subscription')
        );
        
        foreach ($default_options as $option => $value) {
            if (!get_option('fec_' . $option)) {
                update_option('fec_' . $option, $value);
            }
        }
    }
    
    /**
     * Schedule cron events
     */
    private function schedule_events() {
        if (!wp_next_scheduled('fec_hourly_email_check')) {
            wp_schedule_event(time(), 'hourly', 'fec_hourly_email_check');
        }
    }
    
    /**
     * Clear scheduled events
     */
    private function clear_scheduled_events() {
        wp_clear_scheduled_hook('fec_hourly_email_check');
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on pages where our shortcode is used
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'email_financial_client')) {
            wp_enqueue_style('fec-styles', FEC_PLUGIN_URL . 'assets/css/email-client.css', array(), FEC_VERSION);
            wp_enqueue_script('fec-scripts', FEC_PLUGIN_URL . 'assets/js/email-client.js', array('jquery'), FEC_VERSION, true);
            
            // Add localization for AJAX
            wp_localize_script('fec-scripts', 'fecAjax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('fec-ajax-nonce'),
                'loading_emails_text' => __('Loading emails...', 'financial-email-client'),
                'loading_email_text' => __('Loading email...', 'financial-email-client'),
                'failed_load_emails_text' => __('Failed to load emails. Please try again.', 'financial-email-client'),
                'failed_load_email_text' => __('Failed to load email. Please try again.', 'financial-email-client'),
                'connecting_text' => __('Connecting...', 'financial-email-client'),
                'connect_text' => __('Connect Email Account', 'financial-email-client'),
                'connection_failed_text' => __('Connection failed. Please try again.', 'financial-email-client')
            ));
        }
    }
    
    /**
     * Add admin menu items
     */
    public function admin_menu() {
        add_menu_page(
            __('Financial Email Client', 'financial-email-client'),
            __('Financial Email', 'financial-email-client'),
            'manage_options',
            'financial-email-client',
            array($this, 'render_admin_page'),
            'dashicons-email-alt',
            30
        );
        
        add_submenu_page(
            'financial-email-client',
            __('Settings', 'financial-email-client'),
            __('Settings', 'financial-email-client'),
            'manage_options',
            'financial-email-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'financial-email-client',
            __('Analytics', 'financial-email-client'),
            __('Analytics', 'financial-email-client'),
            'manage_options',
            'financial-email-analytics',
            array($this, 'render_analytics_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        include FEC_PLUGIN_DIR . 'admin/views/admin-page.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        include FEC_PLUGIN_DIR . 'admin/views/settings-page.php';
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        include FEC_PLUGIN_DIR . 'admin/views/analytics-page.php';
    }
    
/**
 * Render email client shortcode
 */
public function render_email_client($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<p>' . __('Please login to access your email client.', 'financial-email-client') . '</p>';
    }
    
    // Get user's email accounts
    $user_id = get_current_user_id();
    $accounts = $this->get_user_email_accounts($user_id);
    
    // Use direct output rather than including the template
    $output = '<div class="fec-container">';
    
    if (empty($accounts)) {
        // No email accounts added yet
        $output .= '<div class="fec-setup-account">';
        $output .= '<h2>' . __('Connect Your Email Account', 'financial-email-client') . '</h2>';
        $output .= '<p>' . __('Connect your email account to start monitoring for financial emails, bills, price increases, and subscription renewals.', 'financial-email-client') . '</p>';
        
        $output .= '<form id="fec-connect-email-form" class="fec-form">';
        $output .= '<div class="fec-form-group">';
        $output .= '<label for="fec-email">' . __('Email Address', 'financial-email-client') . '</label>';
        $output .= '<input type="email" id="fec-email" name="email" required>';
        $output .= '</div>';
        
        $output .= '<div class="fec-form-group">';
        $output .= '<label for="fec-provider">' . __('Email Provider', 'financial-email-client') . '</label>';
        $output .= '<select id="fec-provider" name="provider" required>';
        $output .= '<option value="">' . __('Select Provider', 'financial-email-client') . '</option>';
        $output .= '<option value="gmail">' . __('Gmail', 'financial-email-client') . '</option>';
        $output .= '<option value="outlook">' . __('Outlook / Hotmail', 'financial-email-client') . '</option>';
        $output .= '<option value="yahoo">' . __('Yahoo', 'financial-email-client') . '</option>';
        $output .= '<option value="other">' . __('Other IMAP', 'financial-email-client') . '</option>';
        $output .= '</select>';
        $output .= '</div>';
        
        $output .= '<div class="fec-form-group fec-other-settings" style="display: none;">';
        $output .= '<label for="fec-imap-server">' . __('IMAP Server', 'financial-email-client') . '</label>';
        $output .= '<input type="text" id="fec-imap-server" name="imap_server">';
        
        $output .= '<label for="fec-imap-port">' . __('IMAP Port', 'financial-email-client') . '</label>';
        $output .= '<input type="number" id="fec-imap-port" name="imap_port" value="993">';
        
        $output .= '<label for="fec-imap-encryption">' . __('Encryption', 'financial-email-client') . '</label>';
        $output .= '<select id="fec-imap-encryption" name="imap_encryption">';
        $output .= '<option value="ssl">' . __('SSL', 'financial-email-client') . '</option>';
        $output .= '<option value="tls">' . __('TLS', 'financial-email-client') . '</option>';
        $output .= '<option value="none">' . __('None', 'financial-email-client') . '</option>';
        $output .= '</select>';
        $output .= '</div>';
        
        $output .= '<div class="fec-form-group">';
        $output .= '<label for="fec-password">' . __('Password', 'financial-email-client') . '</label>';
        $output .= '<input type="password" id="fec-password" name="password" required>';
        $output .= '<p class="fec-help-text">' . __('Your password is securely encrypted and only used to connect to your email provider.', 'financial-email-client') . '</p>';
        $output .= '</div>';
        
        $output .= '<div class="fec-form-group">';
        $output .= '<button type="submit" class="fec-button fec-button-primary">' . __('Connect Email Account', 'financial-email-client') . '</button>';
        $output .= '</div>';
        
        $output .= '<div id="fec-connection-message" class="fec-message" style="display: none;"></div>';
        $output .= '</form>';
        $output .= '</div>';
    } else {
        // Email client interface
        $output .= '<div class="fec-email-client">';
        
        // Sidebar with folders
        $output .= '<div class="fec-sidebar">';
        $output .= '<div class="fec-account-info">';
        $output .= '<span class="fec-email-address">' . esc_html($accounts[0]->email_address) . '</span>';
        $output .= '<a href="#" class="fec-add-account">' . __('Add Another Account', 'financial-email-client') . '</a>';
        $output .= '</div>';
        
        // Folders
        $output .= '<ul class="fec-folders">';
        $output .= '<li class="fec-folder active" data-folder="INBOX">' . __('Inbox', 'financial-email-client') . '</li>';
        $output .= '<li class="fec-folder" data-folder="INBOX.Sent">' . __('Sent', 'financial-email-client') . '</li>';
        $output .= '<li class="fec-folder" data-folder="INBOX.Drafts">' . __('Drafts', 'financial-email-client') . '</li>';
        $output .= '<li class="fec-folder" data-folder="INBOX.Trash">' . __('Trash', 'financial-email-client') . '</li>';
        $output .= '</ul>';
        
        // Financial folders
        $output .= '<div class="fec-financial-folders">';
        $output .= '<h3>' . __('Financial', 'financial-email-client') . '</h3>';
        $output .= '<ul>';
        $output .= '<li class="fec-virtual-folder" data-type="bills">' . __('Bills & Payments', 'financial-email-client') . '</li>';
        $output .= '<li class="fec-virtual-folder" data-type="price_increases">' . __('Price Increases', 'financial-email-client') . '</li>';
        $output .= '<li class="fec-virtual-folder" data-type="subscriptions">' . __('Subscriptions', 'financial-email-client') . '</li>';
        $output .= '</ul>';
        $output .= '</div>';
        $output .= '</div>'; // End sidebar
        
        // Main content area
        $output .= '<div class="fec-content">';
        
        // Toolbar
        $output .= '<div class="fec-toolbar">';
        $output .= '<div class="fec-search">';
        $output .= '<input type="text" placeholder="' . __('Search emails...', 'financial-email-client') . '" id="fec-search">';
        $output .= '<button id="fec-search-button">' . __('Search', 'financial-email-client') . '</button>';
        $output .= '</div>';
        
        $output .= '<div class="fec-actions">';
        $output .= '<button id="fec-refresh">' . __('Refresh', 'financial-email-client') . '</button>';
        $output .= '</div>';
        $output .= '</div>'; // End toolbar
        
        // Email list container
        $output .= '<div class="fec-email-list-container">';
        $output .= '<div id="fec-email-list" class="fec-email-list">';
        $output .= '<div class="fec-loading">' . __('Loading emails...', 'financial-email-client') . '</div>';
        $output .= '</div>';
        
        $output .= '<div id="fec-email-view" class="fec-email-view">';
        $output .= '<div class="fec-no-email-selected">' . __('Select an email to view', 'financial-email-client') . '</div>';
        $output .= '</div>';
        $output .= '</div>'; // End email list container
        
        $output .= '</div>'; // End content
        $output .= '</div>'; // End email client
        
        // Hidden templates for JavaScript
        // Using data attributes instead of script tags with type="text/template" to avoid potential issues
        $output .= '<div id="fec-email-list-template" style="display:none;" data-template="true">';
        $output .= '<div class="fec-email-item {seen-class}" data-uid="{uid}">';
        $output .= '<div class="fec-email-sender">{sender}</div>';
        $output .= '<div class="fec-email-subject">{subject}</div>';
        $output .= '<div class="fec-email-preview">{preview}</div>';
        $output .= '<div class="fec-email-date">{date}</div>';
        $output .= '<div class="fec-financial-indicator" style="display:{has-financial}">$</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        $output .= '<div id="fec-email-view-template" style="display:none;" data-template="true">';
        $output .= '<div class="fec-email-header">';
        $output .= '<h2 class="fec-email-subject">{subject}</h2>';
        $output .= '<div class="fec-email-meta">';
        $output .= '<div class="fec-email-from">';
        $output .= '<span class="fec-label">' . __('From:', 'financial-email-client') . '</span>';
        $output .= '<span class="fec-value">{from}</span>';
        $output .= '</div>';
        $output .= '<div class="fec-email-to">';
        $output .= '<span class="fec-label">' . __('To:', 'financial-email-client') . '</span>';
        $output .= '<span class="fec-value">{to}</span>';
        $output .= '</div>';
        $output .= '<div class="fec-email-date">';
        $output .= '<span class="fec-label">' . __('Date:', 'financial-email-client') . '</span>';
        $output .= '<span class="fec-value">{date}</span>';
        $output .= '</div>';
        $output .= '</div>'; // End email meta
        $output .= '</div>'; // End email header
        
        $output .= '<div class="fec-email-body">{body}</div>';
        
        $output .= '<div class="fec-email-attachments" style="display:{has-attachments}">';
        $output .= '<h3>' . __('Attachments', 'financial-email-client') . '</h3>';
        $output .= '<div class="fec-attachments-list">{attachments}</div>';
        $output .= '</div>';
        $output .= '</div>'; // End email view template
    }
    
    $output .= '</div>'; // End container
    
    // Add the client-side JavaScript
    $output .= "
   <script>
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
            form.find('button[type=\"submit\"]').prop('disabled', true).text('" . __('Connecting...', 'financial-email-client') . "');
            
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
                messageContainer.addClass('fec-error').text('" . __('Connection failed. Please try again.', 'financial-email-client') . "').show();
            }).always(function() {
                // Re-enable submit button
                form.find('button[type=\"submit\"]').prop('disabled', false).text('" . __('Connect Email Account', 'financial-email-client') . "');
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
    $('#fec-email-list').html('<div class=\"fec-loading\">' + fecAjax.loading_emails_text + '</div>');
    
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
                    $('#fec-email-list').html('<div class=\"fec-error\">Error: Invalid response format</div>');
                }
            } else {
                var errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                console.error('Error loading emails:', errorMsg);
                $('#fec-email-list').html('<div class=\"fec-error\">' + errorMsg + '</div>');
            }
        } catch (e) {
            console.error('Error processing response:', e);
            $('#fec-email-list').html('<div class=\"fec-error\">Error processing response</div>');
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        clearTimeout(loadingTimeout);
        console.error('AJAX request failed:', textStatus, errorThrown);
        $('#fec-email-list').html('<div class=\"fec-error\">AJAX Error: ' + textStatus + '</div>');
    });
}  
            // Display emails in the list
            function displayEmails(data) {
                var html = '';
                
                if (data.messages.length === 0) {
                    html = '<div class=\"fec-no-emails\">" . __('No emails found', 'financial-email-client') . "</div>';
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
                    html += '<div class=\"fec-pagination\">';
                    
                    if (data.page > 1) {
                        html += '<button class=\"fec-prev-page\" data-page=\"' + (data.page - 1) + '\">" . __('Previous', 'financial-email-client') . "</button>';
                    }
                    
                    html += '<span class=\"fec-page-info\">" . __('Page', 'financial-email-client') . " ' + data.page + ' " . __('of', 'financial-email-client') . " ' + data.total_pages + '</span>';
                    
                    if (data.page < data.total_pages) {
                        html += '<button class=\"fec-next-page\" data-page=\"' + (data.page + 1) + '\">" . __('Next', 'financial-email-client') . "</button>';
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
    $('#fec-email-view').html('<div class=\"fec-loading\">' + fecAjax.loading_email_text + '</div>');
    
    $.post(fecAjax.ajax_url, {
        action: 'fetch_email',
        nonce: fecAjax.nonce,
        uid: uid
    }, function(response) {
        if (response.success) {
            displayEmail(response.data);
            
            // Mark as read in UI
            $('.fec-email-item[data-uid=\"' + uid + '\"]').removeClass('fec-unread').addClass('fec-read');
        } else {
            $('#fec-email-view').html('<div class=\"fec-error\">' + response.data.message + '</div>');
        }
    }).fail(function() {
        $('#fec-email-view').html('<div class=\"fec-error\">' + fecAjax.failed_load_email_text + '</div>');
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
                        attachmentsHtml += '<div class=\"fec-attachment-item\">';
                        attachmentsHtml += '<span class=\"fec-attachment-icon\"></span>';
                        attachmentsHtml += '<span class=\"fec-attachment-name\">' + attachment.name + '</span>';
                        attachmentsHtml += '<span class=\"fec-attachment-size\">(' + formatSize(attachment.size) + ')</span>';
                        attachmentsHtml += '<a href=\"#\" class=\"fec-download-attachment\" data-uid=\"' + email.uid + '\" data-part=\"' + attachment.part_number + '\">" . __('Download', 'financial-email-client') . "</a>';
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
    </script>
    ";
    
    return $output;
}
    
    /**
     * Render financial insights shortcode
     */
    public function render_financial_insights($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p>' . __('Please login to access your financial insights.', 'financial-email-client') . '</p>';
        }
        
        $user_id = get_current_user_id();
        
        // Get user's financial insights
        global $wpdb;
        $insights = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}fec_financial_insights WHERE user_id = %d ORDER BY created_at DESC LIMIT 10",
                $user_id
            )
        );
        
        // Start output buffering
        ob_start();
        
        // Include template
        include FEC_PLUGIN_DIR . 'public/views/financial-insights.php';
        
        // Return the buffered content
        return ob_get_clean();
    }
    
    /**
     * Get user email accounts
     */
    private function get_user_email_accounts($user_id) {
        global $wpdb;
        
        $accounts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}fec_email_accounts WHERE user_id = %d",
                $user_id
            )
        );
        
        return $accounts;
    }
    
    /**
     * Handle email connection
     */
    public function handle_email_connection() {
        // Verify nonce
        check_ajax_referer('fec-ajax-nonce', 'nonce');
        
        // Process email connection
        $email = sanitize_email($_POST['email']);
        $provider = sanitize_text_field($_POST['provider']);
        $password = $_POST['password']; // Will be encrypted before storage
        
        // Basic validation
        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Invalid email address.', 'financial-email-client')));
            return;
        }
        
        // Try to connect to the email server
        $connector = new FEC_Email_Connector();
        $connection = $connector->connect($email, $password, $provider);
        
        if (is_wp_error($connection)) {
            wp_send_json_error(array('message' => $connection->get_error_message()));
            return;
        }
        
        // If successful, save connection details
        $user_id = get_current_user_id();
        $this->save_email_connection($user_id, $email, $provider, $connection);
        
        wp_send_json_success(array('message' => __('Email account connected successfully.', 'financial-email-client')));
    }
    
    /**
     * Save email connection
     */
    private function save_email_connection($user_id, $email, $provider, $connection) {
        global $wpdb;
        
        // Encrypt sensitive data
        $encryption_key = get_option('fec_encryption_key');
        $encrypted_data = $this->encrypt_data(json_encode($connection), $encryption_key);
        
        // Store in database
        $wpdb->insert(
            $wpdb->prefix . 'fec_email_accounts',
            array(
                'user_id' => $user_id,
                'email_address' => $email,
                'provider' => $provider,
                'auth_token' => $encrypted_data['token'],
                'server_settings' => $encrypted_data['settings'],
                'created_at' => current_time('mysql'),
                'last_checked' => current_time('mysql')
            )
        );
    }
    
    /**
     * Encrypt sensitive data
     */
    private function encrypt_data($data, $key) {
        // Simple encryption for proof of concept
        // In production, use a more robust encryption method
        $method = 'AES-256-CBC';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
        
        return array(
            'token' => base64_encode($encrypted),
            'settings' => base64_encode($iv)
        );
    }
    
    /**
     * Decrypt sensitive data
     */
    private function decrypt_data($encrypted, $iv, $key) {
        $method = 'AES-256-CBC';
        return openssl_decrypt(base64_decode($encrypted), $method, $key, 0, base64_decode($iv));
    }

    /**
     * Fetch user emails with improved error handling
     */
    public function fetch_user_emails() {
        // Verify nonce
        check_ajax_referer('fec-ajax-nonce', 'nonce');
        
        try {
            $user_id = get_current_user_id();
            $folder = isset($_POST['folder']) ? sanitize_text_field($_POST['folder']) : 'INBOX';
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $per_page = 20;
            
            // Get user's email accounts
            $accounts = $this->get_user_email_accounts($user_id);
            
            if (empty($accounts)) {
                wp_send_json_error(array('message' => __('No email accounts found.', 'financial-email-client')));
                return;
            }
            
            // For simplicity, just use the first account
            $account = $accounts[0];
            
            // Debug: Check account data
            error_log('Account data: ' . print_r($account, true));
            
            // Connect to email account
            $connector = new FEC_Email_Connector();
            $connection = $connector->reconnect($account);
            
            if (is_wp_error($connection)) {
                wp_send_json_error(array('message' => 'Connection error: ' . $connection->get_error_message()));
                return;
            }
            
            // Debug: Check if connection is valid
            error_log('IMAP Connection established: ' . ($connection ? 'Yes' : 'No'));
            
            // Fetch emails
            $emails = $connector->fetch_emails($connection, $folder, $page, $per_page);
            
            // Debug: Check emails data
            error_log('Emails retrieved: ' . count($emails['messages']));
            
            // Analyze emails for financial content
            $analyzer = new FEC_Financial_Analyzer();
            foreach ($emails['messages'] as $key => $email) {
                $financial_data = $analyzer->analyze_email($email);
                if ($financial_data) {
                    $emails['messages'][$key]['financial_data'] = $financial_data;
                }
            }
            
            // Return successful response with emails
            wp_send_json_success($emails);
        } catch (Exception $e) {
            // Log and return the exception
            error_log('Exception in fetch_user_emails: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * Analyze email content
     */
    public function analyze_email_content() {
        // Verify nonce
        check_ajax_referer('fec-ajax-nonce', 'nonce');
        
        $email_id = sanitize_text_field($_POST['email_id']);
        $user_id = get_current_user_id();
        
        // Get the email content
        $connector = new FEC_Email_Connector();
        $email = $connector->get_email_by_id($user_id, $email_id);
        
        if (is_wp_error($email)) {
            wp_send_json_error(array('message' => $email->get_error_message()));
            return;
        }
        
        // Analyze the email
        $analyzer = new FEC_Financial_Analyzer();
        $insights = $analyzer->analyze_email($email);
        
        if ($insights) {
            // Save the insights
            $this->save_financial_insights($user_id, $email_id, $insights);
            
            wp_send_json_success(array(
                'message' => __('Email analyzed successfully.', 'financial-email-client'),
                'insights' => $insights
            ));
        } else {
            wp_send_json_error(array('message' => __('No financial information found in this email.', 'financial-email-client')));
        }
    }
    
    /**
     * Save financial insights
     */
    private function save_financial_insights($user_id, $email_id, $insights) {
        global $wpdb;
        
        foreach ($insights as $insight) {
            $wpdb->insert(
                $wpdb->prefix . 'fec_financial_insights',
                array(
                    'user_id' => $user_id,
                    'email_id' => $email_id,
                    'insight_type' => $insight['type'],
                    'description' => $insight['description'],
                    'amount' => isset($insight['amount']) ? $insight['amount'] : null,
                    'due_date' => isset($insight['due_date']) ? $insight['due_date'] : null,
                    'status' => 'new',
                    'created_at' => current_time('mysql')
                )
            );
        }
    }
}

// Initialize the plugin
$financial_email_client = new Financial_Email_Client();