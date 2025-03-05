<?php
/**
 * Admin dashboard template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Financial Email Client Dashboard', 'financial-email-client'); ?></h1>
    
    <div class="fec-admin-dashboard">
        <div class="fec-admin-card">
            <h2><?php _e('Email Accounts', 'financial-email-client'); ?></h2>
            <?php
            global $wpdb;
            $accounts_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fec_email_accounts");
            ?>
            <p class="fec-stat"><?php echo $accounts_count; ?></p>
            <p><?php _e('Connected email accounts', 'financial-email-client'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=financial-email-settings'); ?>" class="button"><?php _e('Manage Accounts', 'financial-email-client'); ?></a>
        </div>
        
        <div class="fec-admin-card">
            <h2><?php _e('Financial Insights', 'financial-email-client'); ?></h2>
            <?php
            $insights_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}fec_financial_insights");
            ?>
            <p class="fec-stat"><?php echo $insights_count; ?></p>
            <p><?php _e('Financial insights detected', 'financial-email-client'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=financial-email-analytics'); ?>" class="button"><?php _e('View Analytics', 'financial-email-client'); ?></a>
        </div>
    </div>
    
    <div class="fec-admin-section">
        <h2><?php _e('Recent Insights', 'financial-email-client'); ?></h2>
        
        <?php
        $recent_insights = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}fec_financial_insights ORDER BY created_at DESC LIMIT 5");
        
        if (!empty($recent_insights)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Type', 'financial-email-client'); ?></th>
                        <th><?php _e('Description', 'financial-email-client'); ?></th>
                        <th><?php _e('Amount', 'financial-email-client'); ?></th>
                        <th><?php _e('Date', 'financial-email-client'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_insights as $insight): ?>
                        <tr>
                            <td><?php echo esc_html($insight->insight_type); ?></td>
                            <td><?php echo esc_html($insight->description); ?></td>
                            <td><?php echo $insight->amount ? '$' . number_format($insight->amount, 2) : '-'; ?></td>
                            <td><?php echo $insight->due_date ? date('M j, Y', strtotime($insight->due_date)) : date('M j, Y', strtotime($insight->created_at)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No financial insights detected yet.', 'financial-email-client'); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="fec-admin-section">
        <h2><?php _e('Getting Started', 'financial-email-client'); ?></h2>
        
        <p><?php _e('To start using the Financial Email Client, follow these steps:', 'financial-email-client'); ?></p>
        
        <ol>
            <li><?php _e('Connect your email account in the Settings page', 'financial-email-client'); ?></li>
            <li><?php _e('Add the [email_financial_client] shortcode to any page where you want the email client to appear', 'financial-email-client'); ?></li>
            <li><?php _e('Add the [financial_insights] shortcode to display financial summaries', 'financial-email-client'); ?></li>
        </ol>
        
        <p>
            <a href="<?php echo admin_url('admin.php?page=financial-email-settings'); ?>" class="button button-primary"><?php _e('Go to Settings', 'financial-email-client'); ?></a>
        </p>
    </div>
</div>

<style>
.fec-admin-dashboard {
    display: flex;
    margin: 20px 0;
}

.fec-admin-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
    margin-right: 20px;
    width: 250px;
}

.fec-admin-card h2 {
    margin-top: 0;
}

.fec-stat {
    font-size: 36px;
    font-weight: 600;
    margin: 15px 0;
}

.fec-admin-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
    margin: 20px 0;
}

.fec-admin-section h2 {
    margin-top: 0;
}
</style>