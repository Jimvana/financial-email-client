<?php
/**
 * Analytics page template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="fec-analytics-dashboard">
        <!-- Summary Cards -->
        <div class="fec-analytics-summary">
            <div class="fec-analytics-card">
                <h3><?php _e('Total Bills', 'financial-email-client'); ?></h3>
                <?php
                $bill_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}fec_financial_insights WHERE insight_type = %s",
                    'bill_due'
                ));
                ?>
                <p class="fec-stat"><?php echo $bill_count; ?></p>
            </div>
            
            <div class="fec-analytics-card">
                <h3><?php _e('Price Increases', 'financial-email-client'); ?></h3>
                <?php
                $price_increase_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}fec_financial_insights WHERE insight_type = %s",
                    'price_increase'
                ));
                ?>
                <p class="fec-stat"><?php echo $price_increase_count; ?></p>
            </div>
            
            <div class="fec-analytics-card">
                <h3><?php _e('Subscriptions', 'financial-email-client'); ?></h3>
                <?php
                $subscription_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}fec_financial_insights WHERE insight_type = %s",
                    'subscription_renewal'
                ));
                ?>
                <p class="fec-stat"><?php echo $subscription_count; ?></p>
            </div>
            
            <div class="fec-analytics-card">
                <h3><?php _e('Total Amount', 'financial-email-client'); ?></h3>
                <?php
                $total_amount = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}fec_financial_insights");
                ?>
                <p class="fec-stat">$<?php echo number_format($total_amount ? $total_amount : 0, 2); ?></p>
            </div>
        </div>
        
        <!-- Bills Due Soon -->
        <div class="fec-analytics-section">
            <h2><?php _e('Bills Due Soon', 'financial-email-client'); ?></h2>
            
            <?php
            $upcoming_bills = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}fec_financial_insights 
                WHERE insight_type = %s 
                AND due_date >= CURDATE() 
                ORDER BY due_date ASC 
                LIMIT 5",
                'bill_due'
            ));
            
            if (!empty($upcoming_bills)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Description', 'financial-email-client'); ?></th>
                            <th><?php _e('Amount', 'financial-email-client'); ?></th>
                            <th><?php _e('Due Date', 'financial-email-client'); ?></th>
                            <th><?php _e('Status', 'financial-email-client'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_bills as $bill): ?>
                            <tr>
                                <td><?php echo esc_html($bill->description); ?></td>
                                <td>$<?php echo number_format($bill->amount, 2); ?></td>
                                <td><?php echo date('M j, Y', strtotime($bill->due_date)); ?></td>
                                <td>
                                    <?php
                                    $days_until_due = (strtotime($bill->due_date) - time()) / (60 * 60 * 24);
                                    if ($days_until_due < 3) {
                                        echo '<span class="fec-urgent">' . __('Urgent', 'financial-email-client') . '</span>';
                                    } elseif ($days_until_due < 7) {
                                        echo '<span class="fec-upcoming">' . __('Upcoming', 'financial-email-client') . '</span>';
                                    } else {
                                        echo '<span class="fec-future">' . __('Future', 'financial-email-client') . '</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('No upcoming bills detected.', 'financial-email-client'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Recent Price Increases -->
        <div class="fec-analytics-section">
            <h2><?php _e('Recent Price Increases', 'financial-email-client'); ?></h2>
            
            <?php
            $price_increases = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}fec_financial_insights 
                WHERE insight_type = %s 
                ORDER BY created_at DESC 
                LIMIT 5",
                'price_increase'
            ));
            
            if (!empty($price_increases)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Service', 'financial-email-client'); ?></th>
                            <th><?php _e('Old Price', 'financial-email-client'); ?></th>
                            <th><?php _e('New Price', 'financial-email-client'); ?></th>
                            <th><?php _e('Increase', 'financial-email-client'); ?></th>
                            <th><?php _e('Effective Date', 'financial-email-client'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($price_increases as $increase): 
                            // These would be stored in a JSON field in a real implementation
                            $old_price = isset($increase->old_amount) ? $increase->old_amount : 0;
                            $new_price = isset($increase->amount) ? $increase->amount : 0;
                            $percentage = isset($increase->percentage) ? $increase->percentage : (($old_price > 0) ? (($new_price - $old_price) / $old_price * 100) : 0);
                            $effective_date = isset($increase->due_date) ? $increase->due_date : date('Y-m-d', strtotime('+30 days', strtotime($increase->created_at)));
                            ?>
                            <tr>
                                <td><?php echo esc_html($increase->description); ?></td>
                                <td>$<?php echo number_format($old_price, 2); ?></td>
                                <td>$<?php echo number_format($new_price, 2); ?></td>
                                <td><?php echo round($percentage, 1); ?>%</td>
                                <td><?php echo date('M j, Y', strtotime($effective_date)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('No price increases detected.', 'financial-email-client'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Subscription Renewals -->
        <div class="fec-analytics-section">
            <h2><?php _e('Upcoming Subscription Renewals', 'financial-email-client'); ?></h2>
            
            <?php
            $subscriptions = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}fec_financial_insights 
                WHERE insight_type = %s 
                ORDER BY due_date ASC 
                LIMIT 5",
                'subscription_renewal'
            ));
            
            if (!empty($subscriptions)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Service', 'financial-email-client'); ?></th>
                            <th><?php _e('Amount', 'financial-email-client'); ?></th>
                            <th><?php _e('Renewal Date', 'financial-email-client'); ?></th>
                            <th><?php _e('Actions', 'financial-email-client'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $subscription): 
                            $renewal_date = isset($subscription->due_date) ? $subscription->due_date : date('Y-m-d', strtotime('+30 days', strtotime($subscription->created_at)));
                            ?>
                            <tr>
                                <td><?php echo esc_html($subscription->description); ?></td>
                                <td>$<?php echo number_format($subscription->amount, 2); ?></td>
                                <td><?php echo date('M j, Y', strtotime($renewal_date)); ?></td>
                                <td>
                                    <a href="#" class="button-secondary"><?php _e('Set Reminder', 'financial-email-client'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('No subscription renewals detected.', 'financial-email-client'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.fec-analytics-dashboard {
    margin-top: 20px;
}

.fec-analytics-summary {
    display: flex;
    margin-bottom: 20px;
}

.fec-analytics-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 15px;
    margin-right: 15px;
    flex: 1;
    max-width: 200px;
    text-align: center;
}

.fec-analytics-card h3 {
    margin-top: 0;
    font-size: 14px;
}

.fec-stat {
    font-size: 24px;
    font-weight: 600;
    margin: 10px 0;
}

.fec-analytics-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
    margin-bottom: 20px;
}

.fec-analytics-section h2 {
    margin-top: 0;
    font-size: 18px;
}

.fec-urgent {
    color: #d63638;
    font-weight: bold;
}

.fec-upcoming {
    color: #ff8c00;
    font-weight: bold;
}

.fec-future {
    color: #2271b1;
}
</style>