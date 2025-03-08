<?php
/**
 * Settings page template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=financial-email-settings&tab=general" class="nav-tab <?php echo !isset($_GET['tab']) || $_GET['tab'] === 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General Settings', 'financial-email-client'); ?></a>
        <a href="?page=financial-email-settings&tab=accounts" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'accounts' ? 'nav-tab-active' : ''; ?>"><?php _e('Email Accounts', 'financial-email-client'); ?></a>
        <a href="?page=financial-email-settings&tab=analysis" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'analysis' ? 'nav-tab-active' : ''; ?>"><?php _e('Analysis Settings', 'financial-email-client'); ?></a>
        <a href="?page=financial-email-settings&tab=export" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'export' ? 'nav-tab-active' : ''; ?>"><?php _e('Export Data', 'financial-email-client'); ?></a>
    </h2>
    
    <div class="tab-content">
        <?php
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        
        switch ($tab) {
            case 'accounts':
                // Email Accounts Tab Content
                ?>
                <h3><?php _e('Connected Email Accounts', 'financial-email-client'); ?></h3>
                
                <?php
                global $wpdb;
                $accounts = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}fec_email_accounts");
                
                if (!empty($accounts)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Email Address', 'financial-email-client'); ?></th>
                                <th><?php _e('Provider', 'financial-email-client'); ?></th>
                                <th><?php _e('Last Checked', 'financial-email-client'); ?></th>
                                <th><?php _e('Actions', 'financial-email-client'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $account): ?>
                                <tr>
                                    <td><?php echo esc_html($account->email_address); ?></td>
                                    <td><?php echo esc_html($account->provider); ?></td>
                                    <td><?php echo $account->last_checked ? date('M j, Y H:i', strtotime($account->last_checked)) : '-'; ?></td>
                                    <td>
                                        <a href="#" class="button-link-delete" data-id="<?php echo $account->id; ?>"><?php _e('Remove', 'financial-email-client'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('No email accounts connected yet.', 'financial-email-client'); ?></p>
                <?php endif; ?>
                
                <h3><?php _e('Add New Email Account', 'financial-email-client'); ?></h3>
                
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
                            <option value="protonmail"><?php _e('ProtonMail (Bridge)', 'financial-email-client'); ?></option>
                            <option value="zoho"><?php _e('Zoho Mail', 'financial-email-client'); ?></option>
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
                        <button type="submit" class="button button-primary"><?php _e('Connect Email Account', 'financial-email-client'); ?></button>
                    </div>
                    
                    <div id="fec-connection-message" class="fec-message" style="display: none;"></div>
                </form>
                
                <script>
                jQuery(document).ready(function($) {
                    // Toggle other IMAP settings
                    $('#fec-provider').on('change', function() {
                        if ($(this).val() === 'other') {
                            $('.fec-other-settings').show();
                        } else {
                            $('.fec-other-settings').hide();
                        }
                    });
                    
                    // Handle form submission
                    $('#fec-connect-email-form').on('submit', function(e) {
                        e.preventDefault();
                        
                        var form = $(this);
                        var messageContainer = $('#fec-connection-message');
                        
                        // Clear any previous messages
                        messageContainer.removeClass('notice-error notice-success').hide();
                        
                        // Disable submit button
                        form.find('button[type="submit"]').prop('disabled', true).text('<?php _e('Connecting...', 'financial-email-client'); ?>');
                        
                        // Collect form data
                        var formData = {
                            action: 'connect_email_account',
                            nonce: '<?php echo wp_create_nonce('fec-ajax-nonce'); ?>',
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
                        $.post(ajaxurl, formData, function(response) {
                            if (response.success) {
                                messageContainer.addClass('notice notice-success').text(response.data.message).show();
                                
                                // Reload page after successful connection
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            } else {
                                messageContainer.addClass('notice notice-error').text(response.data.message).show();
                            }
                        }).fail(function() {
                            messageContainer.addClass('notice notice-error').text('<?php _e('Connection failed. Please try again.', 'financial-email-client'); ?>').show();
                        }).always(function() {
                            // Re-enable submit button
                            form.find('button[type="submit"]').prop('disabled', false).text('<?php _e('Connect Email Account', 'financial-email-client'); ?>');
                        });
                    });
                });
                </script>
                <?php
                break;
                
            case 'analysis':
                // Analysis Settings Tab Content
                ?>
                <form method="post" action="options.php">
                    <?php settings_fields('fec_analysis_settings_group'); ?>
                    
                    <h3><?php _e('Analysis Types', 'financial-email-client'); ?></h3>
                    <p><?php _e('Select which types of financial information to detect in emails:', 'financial-email-client'); ?></p>
                    
                    <?php
                    $settings = get_option('fec_settings');
                    $analysis_types = isset($settings['analysis_types']) ? $settings['analysis_types'] : array('price_increase', 'bill_due', 'subscription');
                    
                    $available_types = array(
                        'price_increase' => __('Price Increases', 'financial-email-client'),
                        'bill_due' => __('Bills & Due Dates', 'financial-email-client'),
                        'subscription' => __('Subscription Renewals', 'financial-email-client'),
                        'payment_confirmation' => __('Payment Confirmations', 'financial-email-client'),
                        'investment_update' => __('Investment Updates', 'financial-email-client')
                    );
                    ?>
                    
                    <div class="fec-checkbox-group">
                        <?php foreach ($available_types as $type => $label): ?>
                            <label>
                                <input type="checkbox" name="fec_settings[analysis_types][]" value="<?php echo esc_attr($type); ?>" <?php checked(in_array($type, $analysis_types)); ?>>
                                <?php echo esc_html($label); ?>
                            </label><br>
                        <?php endforeach; ?>
                    </div>
                    
                    <h3><?php _e('Scan Frequency', 'financial-email-client'); ?></h3>
                    <p><?php _e('How often should we scan emails for financial content:', 'financial-email-client'); ?></p>
                    
                    <?php
                    $scan_frequency = isset($settings['scan_frequency']) ? $settings['scan_frequency'] : 'hourly';
                    ?>
                    
                    <select name="fec_settings[scan_frequency]">
                        <option value="hourly" <?php selected($scan_frequency, 'hourly'); ?>><?php _e('Hourly', 'financial-email-client'); ?></option>
                        <option value="twice_daily" <?php selected($scan_frequency, 'twice_daily'); ?>><?php _e('Twice Daily', 'financial-email-client'); ?></option>
                        <option value="daily" <?php selected($scan_frequency, 'daily'); ?>><?php _e('Daily', 'financial-email-client'); ?></option>
                    </select>
                    
                    <?php submit_button(); ?>
                </form>
                <?php
                break;
                
            case 'export':
                // Export Data Tab Content
                ?>
                <h3><?php _e('Export Financial Insights', 'financial-email-client'); ?></h3>
                <p><?php _e('Export your financial insights data in various formats:', 'financial-email-client'); ?></p>
                
                <form id="fec-export-form" class="fec-form">
                    <div class="fec-form-group">
                        <label for="fec-export-format"><?php _e('Export Format', 'financial-email-client'); ?></label>
                        <select id="fec-export-format" name="format" required>
                            <option value="csv"><?php _e('CSV (.csv)', 'financial-email-client'); ?></option>
                            <option value="pdf"><?php _e('PDF (.pdf)', 'financial-email-client'); ?></option>
                            <option value="json"><?php _e('JSON (.json)', 'financial-email-client'); ?></option>
                        </select>
                    </div>
                    
                    <div class="fec-form-group">
                        <label for="fec-export-type"><?php _e('Insight Type', 'financial-email-client'); ?></label>
                        <select id="fec-export-type" name="type">
                            <option value=""><?php _e('All Types', 'financial-email-client'); ?></option>
                            <option value="bill_due"><?php _e('Bills & Due Dates', 'financial-email-client'); ?></option>
                            <option value="price_increase"><?php _e('Price Increases', 'financial-email-client'); ?></option>
                            <option value="subscription_renewal"><?php _e('Subscription Renewals', 'financial-email-client'); ?></option>
                            <option value="payment_confirmation"><?php _e('Payment Confirmations', 'financial-email-client'); ?></option>
                            <option value="investment_update"><?php _e('Investment Updates', 'financial-email-client'); ?></option>
                        </select>
                    </div>
                    
                    <div class="fec-form-group">
                        <label for="fec-export-date-from"><?php _e('Date From', 'financial-email-client'); ?></label>
                        <input type="date" id="fec-export-date-from" name="date_from">
                    </div>
                    
                    <div class="fec-form-group">
                        <label for="fec-export-date-to"><?php _e('Date To', 'financial-email-client'); ?></label>
                        <input type="date" id="fec-export-date-to" name="date_to">
                    </div>
                    
                    <div class="fec-form-group">
                        <label for="fec-export-status"><?php _e('Status', 'financial-email-client'); ?></label>
                        <select id="fec-export-status" name="status">
                            <option value=""><?php _e('All Statuses', 'financial-email-client'); ?></option>
                            <option value="new"><?php _e('New', 'financial-email-client'); ?></option>
                            <option value="pending"><?php _e('Pending', 'financial-email-client'); ?></option>
                            <option value="paid"><?php _e('Paid', 'financial-email-client'); ?></option>
                            <option value="overdue"><?php _e('Overdue', 'financial-email-client'); ?></option>
                        </select>
                    </div>
                    
                    <div class="fec-form-group">
                        <button type="submit" class="button button-primary"><?php _e('Export Data', 'financial-email-client'); ?></button>
                    </div>
                    
                    <div id="fec-export-message" class="fec-message" style="display: none;"></div>
                </form>
                
                <script>
                jQuery(document).ready(function($) {
                    // Handle form submission
                    $('#fec-export-form').on('submit', function(e) {
                        e.preventDefault();
                        
                        var form = $(this);
                        var messageContainer = $('#fec-export-message');
                        
                        // Clear any previous messages
                        messageContainer.removeClass('notice-error notice-success').hide();
                        
                        // Disable submit button
                        form.find('button[type="submit"]').prop('disabled', true).text('<?php _e('Processing...', 'financial-email-client'); ?>');
                        
                        // Collect form data
                        var formData = form.serialize();
                        formData += '&action=export_insights&nonce=<?php echo wp_create_nonce("fec-ajax-nonce"); ?>';
                        
                        // Send AJAX request
                        $.post(ajaxurl, formData, function(response) {
                            if (response.success) {
                                messageContainer.addClass('notice notice-success').html(
                                    response.data.message + '<br><a href="' + response.data.download_url + '" class="button button-secondary" download><?php _e("Download File", "financial-email-client"); ?></a>'
                                ).show();
                            } else {
                                messageContainer.addClass('notice notice-error').text(response.data.message).show();
                            }
                        }).fail(function() {
                            messageContainer.addClass('notice notice-error').text('<?php _e("Export failed. Please try again.", "financial-email-client"); ?>').show();
                        }).always(function() {
                            // Re-enable submit button
                            form.find('button[type="submit"]').prop('disabled', false).text('<?php _e("Export Data", "financial-email-client"); ?>');
                        });
                    });
                });
                </script>
                <?php
                break;
                
            default:
                // General Settings Tab Content
                ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('fec_settings_group');
                    do_settings_sections('fec_settings');
                    submit_button();
                    ?>
                </form>
                <?php
                break;
        }
        ?>
    </div>
</div>
