<?php
/**
 * Admin Settings Class
 * Handles admin settings and options pages
 */
class Admin_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register settings 
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Register settings group
        register_setting(
            'fec_settings_group',
            'fec_settings',
            array($this, 'sanitize_settings')
        );
        
        // Add settings section
        add_settings_section(
            'fec_general_settings',
            __('General Settings', 'financial-email-client'),
            array($this, 'general_settings_callback'),
            'fec_settings'
        );
        
        // Add settings fields
        add_settings_field(
            'scan_frequency',
            __('Scan Frequency', 'financial-email-client'),
            array($this, 'scan_frequency_callback'),
            'fec_settings',
            'fec_general_settings'
        );
        
        add_settings_field(
            'providers_enabled',
            __('Email Providers', 'financial-email-client'),
            array($this, 'providers_enabled_callback'),
            'fec_settings',
            'fec_general_settings'
        );
    }
    
    /**
     * General settings section callback
     */
    public function general_settings_callback() {
        echo '<p>' . __('Configure general settings for the Financial Email Client.', 'financial-email-client') . '</p>';
    }
    
    /**
     * Scan frequency field callback
     */
    public function scan_frequency_callback() {
        $settings = get_option('fec_settings');
        $frequency = isset($settings['scan_frequency']) ? $settings['scan_frequency'] : 'hourly';
        
        ?>
        <select name="fec_settings[scan_frequency]" id="fec_scan_frequency">
            <option value="hourly" <?php selected($frequency, 'hourly'); ?>><?php _e('Hourly', 'financial-email-client'); ?></option>
            <option value="twice_daily" <?php selected($frequency, 'twice_daily'); ?>><?php _e('Twice Daily', 'financial-email-client'); ?></option>
            <option value="daily" <?php selected($frequency, 'daily'); ?>><?php _e('Daily', 'financial-email-client'); ?></option>
        </select>
        <p class="description"><?php _e('How often to scan emails for financial content.', 'financial-email-client'); ?></p>
        <?php
    }
    
    /**
     * Providers enabled field callback
     */
    public function providers_enabled_callback() {
        $settings = get_option('fec_settings');
        $providers = isset($settings['providers_enabled']) ? $settings['providers_enabled'] : array('gmail', 'outlook', 'yahoo');
        
        $available_providers = array(
            'gmail' => __('Gmail', 'financial-email-client'),
            'outlook' => __('Outlook', 'financial-email-client'),
            'yahoo' => __('Yahoo Mail', 'financial-email-client'),
            'other' => __('Other IMAP', 'financial-email-client')
        );
        
        foreach ($available_providers as $provider => $label) {
            ?>
            <label>
                <input type="checkbox" name="fec_settings[providers_enabled][]" value="<?php echo esc_attr($provider); ?>" <?php checked(in_array($provider, $providers)); ?>>
                <?php echo esc_html($label); ?>
            </label><br>
            <?php
        }
        
        echo '<p class="description">' . __('Select which email providers to support.', 'financial-email-client') . '</p>';
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Sanitize scan frequency
        if (isset($input['scan_frequency'])) {
            $sanitized['scan_frequency'] = sanitize_text_field($input['scan_frequency']);
        }
        
        // Sanitize providers enabled
        if (isset($input['providers_enabled']) && is_array($input['providers_enabled'])) {
            $sanitized['providers_enabled'] = array_map('sanitize_text_field', $input['providers_enabled']);
        }
        
        return $sanitized;
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('fec_settings_group');
                do_settings_sections('fec_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

// Initialize the admin settings
if (is_admin()) {
    $admin_settings = new Admin_Settings();
}