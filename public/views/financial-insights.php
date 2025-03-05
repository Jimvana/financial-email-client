<?php
/**
 * Financial insights template
 * This template is used by the shortcode to display financial insights
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="fec-financial-insights-container">
    <h2><?php _e('Financial Insights', 'financial-email-client'); ?></h2>
    
    <?php if (empty($insights)): ?>
        <div class="fec-no-insights">
            <p><?php _e('No financial insights detected yet. Connect your email account to start monitoring for financial information.', 'financial-email-client'); ?></p>
            <p><a href="<?php echo esc_url(add_query_arg('shortcode', 'email_financial_client', get_permalink())); ?>" class="fec-button"><?php _e('Connect Email Account', 'financial-email-client'); ?></a></p>
        </div>
    <?php else: ?>
        <!-- Financial Summary -->
        <div class="fec-summary-cards">
            <?php
            // Calculate summary stats
            $total_bills = 0;
            $upcoming_bills = 0;
            $total_bill_amount = 0;
            $price_increases = 0;
            $subscription_renewals = 0;
            
            foreach ($insights as $insight) {
                if ($insight->insight_type === 'bill_due') {
                    $total_bills++;
                    $total_bill_amount += $insight->amount;
                    
                    // Check if bill is upcoming (due in next 30 days)
                    if ($insight->due_date && strtotime($insight->due_date) > time() && strtotime($insight->due_date) < strtotime('+30 days')) {
                        $upcoming_bills++;
                    }
                } elseif ($insight->insight_type === 'price_increase') {
                    $price_increases++;
                } elseif ($insight->insight_type === 'subscription_renewal') {
                    $subscription_renewals++;
                }
            }
            ?>
            
            <div class="fec-summary-card">
                <h3><?php _e('Bills', 'financial-email-client'); ?></h3>
                <div class="fec-summary-stat"><?php echo $total_bills; ?></div>
                <div class="fec-summary-subtext"><?php echo sprintf(_n('%d upcoming', '%d upcoming', $upcoming_bills, 'financial-email-client'), $upcoming_bills); ?></div>
            </div>
            
            <div class="fec-summary-card">
                <h3><?php _e('Amount Due', 'financial-email-client'); ?></h3>
                <div class="fec-summary-stat">$<?php echo number_format($total_bill_amount, 2); ?></div>
                <div class="fec-summary-subtext"><?php _e('total bill amount', 'financial-email-client'); ?></div>
            </div>
            
            <div class="fec-summary-card">
                <h3><?php _e('Price Increases', 'financial-email-client'); ?></h3>
                <div class="fec-summary-stat"><?php echo $price_increases; ?></div>
                <div class="fec-summary-subtext"><?php _e('detected price changes', 'financial-email-client'); ?></div>
            </div>
            
            <div class="fec-summary-card">
                <h3><?php _e('Subscriptions', 'financial-email-client'); ?></h3>
                <div class="fec-summary-stat"><?php echo $subscription_renewals; ?></div>
                <div class="fec-summary-subtext"><?php _e('subscription renewals', 'financial-email-client'); ?></div>
            </div>
        </div>
        
        <!-- Upcoming Bills -->
        <div class="fec-insights-section">
            <h3><?php _e('Upcoming Bills', 'financial-email-client'); ?></h3>
            
            <?php
            $upcoming_bill_insights = array_filter($insights, function($insight) {
                return $insight->insight_type === 'bill_due' && 
                       $insight->due_date && 
                       strtotime($insight->due_date) > time() && 
                       strtotime($insight->due_date) < strtotime('+30 days');
            });
            
            usort($upcoming_bill_insights, function($a, $b) {
                return strtotime($a->due_date) - strtotime($b->due_date);
            });
            
            $upcoming_bill_insights = array_slice($upcoming_bill_insights, 0, 5);
            ?>
            
            <?php if (!empty($upcoming_bill_insights)): ?>
                <div class="fec-insights-table-container">
                    <table class="fec-insights-table">
                        <thead>
                            <tr>
                                <th><?php _e('Description', 'financial-email-client'); ?></th>
                                <th><?php _e('Amount', 'financial-email-client'); ?></th>
                                <th><?php _e('Due Date', 'financial-email-client'); ?></th>
                                <th><?php _e('Status', 'financial-email-client'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_bill_insights as $bill): ?>
                                <tr>
                                    <td><?php echo esc_html($bill->description); ?></td>
                                    <td>$<?php echo number_format($bill->amount, 2); ?></td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($bill->due_date)); ?></td>
                                    <td>
                                        <?php
                                        $days_until_due = (strtotime($bill->due_date) - time()) / (60 * 60 * 24);
                                        if ($days_until_due < 3) {
                                            echo '<span class="fec-urgent">' . __('Urgent', 'financial-email-client') . '</span>';
                                        } elseif ($days_until_due < 7) {
                                            echo '<span class="fec-upcoming">' . __('Due Soon', 'financial-email-client') . '</span>';
                                        } else {
                                            echo '<span class="fec-future">' . __('Upcoming', 'financial-email-client') . '</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p><?php _e('No upcoming bills detected.', 'financial-email-client'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Recent Price Increases -->
        <div class="fec-insights-section">
            <h3><?php _e('Recent Price Increases', 'financial-email-client'); ?></h3>
            
            <?php
            $price_increase_insights = array_filter($insights, function($insight) {
                return $insight->insight_type === 'price_increase';
            });
            
            usort($price_increase_insights});
            
            usort($price_increase_insights, function($a, $b) {
                return strtotime($b->created_at) - strtotime($a->created_at);
            });
            
            $price_increase_insights = array_slice($price_increase_insights, 0, 5);
            ?>
            
            <?php if (!empty($price_increase_insights)): ?>
                <div class="fec-insights-table-container">
                    <table class="fec-insights-table">
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
                            <?php foreach ($price_increase_insights as $increase): 
                                // In a real implementation, these would be stored as JSON metadata
                                // For this template, we'll use placeholder values for demonstration
                                $old_price = isset($increase->old_amount) ? $increase->old_amount : ($increase->amount * 0.9);
                                $new_price = $increase->amount;
                                $percentage = isset($increase->percentage) ? $increase->percentage : (($new_price - $old_price) / $old_price * 100);
                                $effective_date = isset($increase->due_date) ? $increase->due_date : date('Y-m-d', strtotime('+30 days', strtotime($increase->created_at)));
                                ?>
                                <tr>
                                    <td><?php echo esc_html($increase->description); ?></td>
                                    <td>$<?php echo number_format($old_price, 2); ?></td>
                                    <td>$<?php echo number_format($new_price, 2); ?></td>
                                    <td><?php echo round($percentage, 1); ?>%</td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($effective_date)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p><?php _e('No price increases detected.', 'financial-email-client'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Subscription Renewals -->
        <div class="fec-insights-section">
            <h3><?php _e('Upcoming Subscription Renewals', 'financial-email-client'); ?></h3>
            
            <?php
            $subscription_insights = array_filter($insights, function($insight) {
                return $insight->insight_type === 'subscription_renewal';
            });
            
            usort($subscription_insights, function($a, $b) {
                $a_date = isset($a->due_date) ? $a->due_date : $a->created_at;
                $b_date = isset($b->due_date) ? $b->due_date : $b->created_at;
                return strtotime($a_date) - strtotime($b_date);
            });
            
            $subscription_insights = array_slice($subscription_insights, 0, 5);
            ?>
            
            <?php if (!empty($subscription_insights)): ?>
                <div class="fec-insights-table-container">
                    <table class="fec-insights-table">
                        <thead>
                            <tr>
                                <th><?php _e('Service', 'financial-email-client'); ?></th>
                                <th><?php _e('Amount', 'financial-email-client'); ?></th>
                                <th><?php _e('Renewal Date', 'financial-email-client'); ?></th>
                                <th><?php _e('Frequency', 'financial-email-client'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscription_insights as $subscription): 
                                $renewal_date = isset($subscription->due_date) ? $subscription->due_date : date('Y-m-d', strtotime('+30 days', strtotime($subscription->created_at)));
                                // Placeholder for frequency since it might not be stored in this version
                                $frequency = __('Monthly', 'financial-email-client');
                                ?>
                                <tr>
                                    <td><?php echo esc_html($subscription->description); ?></td>
                                    <td>$<?php echo number_format($subscription->amount, 2); ?></td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($renewal_date)); ?></td>
                                    <td><?php echo esc_html($frequency); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p><?php _e('No subscription renewals detected.', 'financial-email-client'); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Financial Insights Styles */
.fec-financial-insights-container {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.fec-financial-insights-container h2 {
    margin-bottom: 20px;
}

.fec-no-insights {
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    padding: 20px;
    border-radius: 4px;
    text-align: center;
}

.fec-button {
    display: inline-block;
    padding: 10px 15px;
    background-color: #0073aa;
    color: #fff;
    text-decoration: none;
    border-radius: 4px;
    margin-top: 10px;
}

.fec-button:hover {
    background-color: #005f8b;
    color: #fff;
}

.fec-summary-cards {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px 20px;
}

.fec-summary-card {
    flex: 1;
    min-width: 200px;
    margin: 0 10px 20px;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.fec-summary-card h3 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 16px;
    color: #666;
}

.fec-summary-stat {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 5px;
}

.fec-summary-subtext {
    font-size: 12px;
    color: #666;
}

.fec-insights-section {
    margin-bottom: 30px;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.fec-insights-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
}

.fec-insights-table-container {
    overflow-x: auto;
}

.fec-insights-table {
    width: 100%;
    border-collapse: collapse;
}

.fec-insights-table th,
.fec-insights-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.fec-insights-table th {
    background-color: #f5f5f5;
    font-weight: 600;
}

.fec-urgent {
    color: #d63638;
    font-weight: 600;
}

.fec-upcoming {
    color: #ff8c00;
    font-weight: 600;
}

.fec-future {
    color: #2271b1;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .fec-summary-cards {
        flex-direction: column;
    }
    
    .fec-summary-card {
        min-width: 100%;
    }
}
</style>