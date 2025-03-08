<?php
/**
 * Financial Analyzer Class
 * Analyzes email content for financial information
 */
class FEC_Financial_Analyzer {
    /**
     * Debug mode
     *
     * @var boolean
     */
    private $debug_mode = false;

    /**
     * Constructor
     */
    public function __construct() {
        // Check if debug mode is enabled via query parameter
        if (isset($_GET['fec_debug']) && $_GET['fec_debug'] == 1 && isset($_GET['key'])) {
            // Verify secret key for security
            $debug_key = $_GET['key'];
            $stored_key = get_option('fec_debug_key', '');
            
            if (!empty($stored_key) && $debug_key === $stored_key) {
                $this->debug_mode = true;
                // Add debug action to log analysis results
                add_action('fec_debug_log', array($this, 'log_debug_info'));
                
    /**
     * Check for investment notifications
     *
     * @param array $email Email data
     * @return array|false Investment insights or false if none found
     */
    private function check_investment_notifications($email) {
        $insights = array();
        $body = $email['body'];
        $subject = $email['subject'];
        
        // Keywords that might indicate an investment notification
        $investment_keywords = array(
            'investment',
            'portfolio',
            'stock',
            'fund',
            'dividend',
            'securities',
            'trading',
            'brokerage',
            'market update',
            'investment summary',
            'quarterly statement',
            'annual report',
            'capital gain',
            'ETF',
            'mutual fund',
            'retirement account',
            '401k',
            'IRA',
            'performance',
            'asset allocation'
        );
        
        // Check if any investment keywords are in the subject or body
        $is_investment_email = false;
        foreach ($investment_keywords as $keyword) {
            if (stripos($subject, $keyword) !== false || stripos($body, $keyword) !== false) {
                $is_investment_email = true;
                break;
            }
        }
        
        if (!$is_investment_email) {
            return false;
        }
        
        // Look for performance indicators
        $performance_change = null;
        $performance_patterns = array(
            '/(?:increased|decreased|up|down|gained|lost)\s+by\s+(\d+(?:\.\d+)?)(?:\s*)?(?:%|percent)/i',
            '/(\d+(?:\.\d+)?)(?:\s*)?(?:%|percent)\s+(?:increase|decrease|gain|loss)/i',
            '/(?:returned|return of)\s+(\d+(?:\.\d+)?)(?:\s*)?(?:%|percent)/i'
        );
        
        foreach ($performance_patterns as $pattern) {
            if (preg_match($pattern, $body, $matches)) {
                $performance_change = floatval($matches[1]);
                // Check for negative indicators
                if (preg_match('/(?:decreased|down|lost|loss|negative)/i', $body, $neg_matches)) {
                    $performance_change = -$performance_change;
                }
                break;
            }
        }
        
        // Look for total value
        $total_value = $this->extract_amount($body);
        
        // Look for statement date patterns
        $statement_date = null;
        $date_patterns = array(
            '/(?:statement|report|as of)\s+(?:date|period)?\s*:?\s*
            (\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{4})/ix',
            '/(?:statement|report|as of)\s+(?:date|period)?\s*:?\s*
            ([A-Za-z]+\s+\d{1,2}(?:st|nd|rd|th)?,\s*\d{4})/ix'
        );
        
        foreach ($date_patterns as $pattern) {
            if (preg_match($pattern, $body, $matches)) {
                $statement_date = date('Y-m-d', strtotime($matches[1]));
                break;
            }
        }
        
        if ($performance_change !== null || $total_value) {
            $insights[] = array(
                'type' => 'investment_update',
                'description' => 'Investment portfolio update',
                'performance_change' => $performance_change,
                'total_value' => $total_value,
                'statement_date' => $statement_date,
                'source' => array(
                    'email_subject' => $subject,
                    'from' => $email['from']
                )
            );
        }
        
        return $insights;
    }
}
        }
    }
    
    /**
     * Log debug information
     *
     * @param mixed $data Data to log
     */
    public function log_debug_info($data) {
        if (!$this->debug_mode) {
            return;
        }
        
        $log_file = WP_CONTENT_DIR . '/fec-debug.log';
        $log_data = date('Y-m-d H:i:s') . ' - ' . print_r($data, true) . "\n";
        
        file_put_contents($log_file, $log_data, FILE_APPEND);
    }
    
    /**
     * Analyze email for financial information
     *
     * @param array $email Email data
     * @return array|false Financial insights or false if none found
     */
    public function analyze_email($email) {
        $insights = array();
        
        // Log the start of analysis if in debug mode
        if ($this->debug_mode) {
            do_action('fec_debug_log', array(
                'action' => 'analyze_start',
                'email_subject' => $email['subject'],
                'from' => $email['from'],
                'timestamp' => current_time('timestamp')
            ));
        }
        
        // Check for bill due dates
        $bill_insights = $this->check_bill_due_dates($email);
        if ($bill_insights) {
            $insights = array_merge($insights, $bill_insights);
        }
        
        // Check for price increases
        $price_insights = $this->check_price_increases($email);
        if ($price_insights) {
            $insights = array_merge($insights, $price_insights);
        }
        
        // Check for subscription renewals
        $subscription_insights = $this->check_subscription_renewals($email);
        if ($subscription_insights) {
            $insights = array_merge($insights, $subscription_insights);
        }
        
        // Check for payment confirmations
        $payment_insights = $this->check_payment_confirmations($email);
        if ($payment_insights) {
            $insights = array_merge($insights, $payment_insights);
        }
        
        // Check for investment notifications
        $investment_insights = $this->check_investment_notifications($email);
        if ($investment_insights) {
            $insights = array_merge($insights, $investment_insights);
        }
        
        // Log the insights found if in debug mode
        if ($this->debug_mode) {
            do_action('fec_debug_log', array(
                'action' => 'analyze_complete',
                'email_subject' => $email['subject'],
                'insights_count' => count($insights),
                'insights' => $insights,
                'timestamp' => current_time('timestamp')
            ));
        }
        
        // Return insights if any found, otherwise false
        return !empty($insights) ? $insights : false;
    }
    
    /**
     * Check for bill due dates
     *
     * @param array $email Email data
     * @return array|false Bill insights or false if none found
     */
    private function check_bill_due_dates($email) {
        $insights = array();
        $body = $email['body'];
        $subject = $email['subject'];
        
        // Keywords that might indicate a bill
        $bill_keywords = array(
            'bill',
            'invoice',
            'statement',
            'payment due',
            'amount due',
            'due date',
            'please pay',
            'utility bill',
            'electricity bill',
            'gas bill',
            'water bill',
            'phone bill',
            'credit card statement',
            'monthly statement',
            'payment reminder',
            'account summary',
            'balance due',
            'minimum payment',
            'payment required',
            'mortgage payment',
            'loan payment',
            'internet bill',
            'cable bill',
            'subscription fee',
            'membership fee'
        );
        
        // Check if any bill keywords are in the subject or body
        $is_bill_email = false;
        foreach ($bill_keywords as $keyword) {
            if (stripos($subject, $keyword) !== false || stripos($body, $keyword) !== false) {
                $is_bill_email = true;
                break;
            }
        }
        
        if (!$is_bill_email) {
            return false;
        }
        
        // Look for due date patterns
        $due_date = $this->extract_due_date($body);
        
        // Look for amount patterns
        $amount = $this->extract_amount($body);
        
        if ($due_date || $amount) {
            $insights[] = array(
                'type' => 'bill_due',
                'description' => 'Bill or payment due',
                'due_date' => $due_date,
                'amount' => $amount,
                'source' => array(
                    'email_subject' => $subject,
                    'from' => $email['from']
                )
            );
        }
        
        return $insights;
    }
    
    /**
     * Check for price increases
     *
     * @param array $email Email data
     * @return array|false Price increase insights or false if none found
     */
    private function check_price_increases($email) {
        $insights = array();
        $body = $email['body'];
        $subject = $email['subject'];
        
        // Keywords that might indicate a price increase
        $price_increase_keywords = array(
            'price increase',
            'rate increase',
            'price change',
            'new pricing',
            'price adjustment',
            'inflation adjustment',
            'fee increase',
            'raising our prices',
            'updating our pricing',
            'changes to your subscription',
            'changes to your plan'
        );
        
        // Check if any price increase keywords are in the subject or body
        $is_price_increase_email = false;
        foreach ($price_increase_keywords as $keyword) {
            if (stripos($subject, $keyword) !== false || stripos($body, $keyword) !== false) {
                $is_price_increase_email = true;
                break;
            }
        }
        
        if (!$is_price_increase_email) {
            return false;
        }
        
        // Look for percentage patterns
        $percentage_patterns = array(
            '/(?:increase|change|adjustment|raising|up)\s+(?:of|by)\s+(\d+(?:\.\d+)?)(?:\s*)?(?:%|percent)/i',
            '/(\d+(?:\.\d+)?)(?:\s*)?(?:%|percent)\s+(?:increase|change|adjustment|higher|more)/i'
        );
        
        $percentage = null;
        foreach ($percentage_patterns as $pattern) {
            if (preg_match($pattern, $body, $matches)) {
                $percentage = floatval($matches[1]);
                break;
            }
        }
        
        // Look for amount patterns
        $old_amount = null;
        $new_amount = null;
        
        // Pattern for "from $X to $Y"
        if (preg_match('/from\s+\$?(\d+(?:\.\d+)?)\s+to\s+\$?(\d+(?:\.\d+)?)/i', $body, $matches)) {
            $old_amount = floatval($matches[1]);
            $new_amount = floatval($matches[2]);
        }
        
        // Look for effective date patterns
        $effective_date = $this->extract_effective_date($body);
        
        if ($percentage || ($old_amount && $new_amount)) {
            $insights[] = array(
                'type' => 'price_increase',
                'description' => 'Price increase detected',
                'percentage' => $percentage,
                'old_amount' => $old_amount,
                'new_amount' => $new_amount,
                'effective_date' => $effective_date,
                'source' => array(
                    'email_subject' => $subject,
                    'from' => $email['from']
                )
            );
        }
        
        return $insights;
    }
    
    /**
     * Check for subscription renewals
     *
     * @param array $email Email data
     * @return array|false Subscription insights or false if none found
     */
    private function check_subscription_renewals($email) {
        $insights = array();
        $body = $email['body'];
        $subject = $email['subject'];
        
        // Keywords that might indicate a subscription renewal
        $subscription_keywords = array(
            'subscription',
            'membership',
            'renew',
            'renewal',
            'auto-renewal',
            'recurring',
            'will renew',
            'will be renewed',
            'will be charged',
            'will automatically renew',
            'subscription confirmation',
            'membership confirmation'
        );
        
        // Check if any subscription keywords are in the subject or body
        $is_subscription_email = false;
        foreach ($subscription_keywords as $keyword) {
            if (stripos($subject, $keyword) !== false || stripos($body, $keyword) !== false) {
                $is_subscription_email = true;
                break;
            }
        }
        
        if (!$is_subscription_email) {
            return false;
        }
        
        // Look for renewal date patterns
        $renewal_date = $this->extract_renewal_date($body);
        
        // Look for amount patterns
        $amount = $this->extract_amount($body);
        
        if ($renewal_date || $amount) {
            $insights[] = array(
                'type' => 'subscription_renewal',
                'description' => 'Subscription renewal',
                'renewal_date' => $renewal_date,
                'amount' => $amount,
                'source' => array(
                    'email_subject' => $subject,
                    'from' => $email['from']
                )
            );
        }
        
        return $insights;
    }
    
    /**
     * Check for payment confirmations
     *
     * @param array $email Email data
     * @return array|false Payment insights or false if none found
     */
    private function check_payment_confirmations($email) {
        $insights = array();
        $body = $email['body'];
        $subject = $email['subject'];
        
        // Keywords that might indicate a payment confirmation
        $payment_keywords = array(
            'payment confirmation',
            'payment receipt',
            'payment successful',
            'payment processed',
            'thank you for your payment',
            'payment received',
            'transaction confirmation',
            'order confirmation',
            'receipt',
            'invoice paid',
            'payment completed'
        );
        
        // Check if any payment keywords are in the subject or body
        $is_payment_email = false;
        foreach ($payment_keywords as $keyword) {
            if (stripos($subject, $keyword) !== false || stripos($body, $keyword) !== false) {
                $is_payment_email = true;
                break;
            }
        }
        
        if (!$is_payment_email) {
            return false;
        }
        
        // Look for amount patterns
        $amount = $this->extract_amount($body);
        
        // Look for payment date patterns
        $payment_date = $this->extract_payment_date($body);
        
        if ($amount) {
            $insights[] = array(
                'type' => 'payment_confirmation',
                'description' => 'Payment confirmation',
                'amount' => $amount,
                'payment_date' => $payment_date,
                'source' => array(
                    'email_subject' => $subject,
                    'from' => $email['from']
                )
            );
        }
        
        return $insights;
    }
    
    /**
     * Extract due date from text
     *
     * @param string $text Text to search
     * @return string|null Due date in Y-m-d format or null if not found
     */
    private function extract_due_date($text) {
        // Common date formats in emails
        $date_patterns = array(
            // MM/DD/YYYY
            '/due\s+(?:date|on|by)?\s*:?\s*(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/i',
            '/(?:due|pay)(?:\s+by|\s+before|\s+on)?\s+(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/i',
            
            // Month DD, YYYY
            '/due\s+(?:date|on|by)?\s*:?\s*([A-Za-z]+)\s+(\d{1,2})(?:st|nd|rd|th)?,\s*(\d{4})/i',
            '/(?:due|pay)(?:\s+by|\s+before|\s+on)?\s+([A-Za-z]+)\s+(\d{1,2})(?:st|nd|rd|th)?,\s*(\d{4})/i',
            
            // DD Month YYYY
            '/due\s+(?:date|on|by)?\s*:?\s*(\d{1,2})(?:st|nd|rd|th)?\s+([A-Za-z]+)\s+(\d{4})/i',
            '/(?:due|pay)(?:\s+by|\s+before|\s+on)?\s+(\d{1,2})(?:st|nd|rd|th)?\s+([A-Za-z]+)\s+(\d{4})/i'
        );
        
        foreach ($date_patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // Check which format was matched
                if (is_numeric($matches[1]) && is_numeric($matches[2]) && is_numeric($matches[3])) {
                    // MM/DD/YYYY format
                    $month = $matches[1];
                    $day = $matches[2];
                    $year = $matches[3];
                    return date('Y-m-d', strtotime("$year-$month-$day"));
                } elseif (is_numeric($matches[2]) && is_numeric($matches[3])) {
                    // Month DD, YYYY format
                    $month = date('m', strtotime($matches[1]));
                    $day = $matches[2];
                    $year = $matches[3];
                    return date('Y-m-d', strtotime("$year-$month-$day"));
                } elseif (is_numeric($matches[1]) && is_numeric($matches[3])) {
                    // DD Month YYYY format
                    $day = $matches[1];
                    $month = date('m', strtotime($matches[2]));
                    $year = $matches[3];
                    return date('Y-m-d', strtotime("$year-$month-$day"));
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extract amount from text
     *
     * @param string $text Text to search
     * @return float|null Amount or null if not found
     */
    private function extract_amount($text) {
        // Common amount formats in emails
        $amount_patterns = array(
            '/(?:amount|total|payment|charge|fee|price)\s+(?:due|of|:)?\s*\$?(\d+(?:,\d{3})*(?:\.\d{2})?)/i',
            '/\$(\d+(?:,\d{3})*(?:\.\d{2})?)\s+(?:amount|total|payment|charge|fee|price)/i',
            '/\$(\d+(?:,\d{3})*(?:\.\d{2})?)/i'
        );
        
        foreach ($amount_patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // Remove commas before converting to float
                return floatval(str_replace(',', '', $matches[1]));
            }
        }
        
        return null;
    }
    
    /**
     * Extract effective date from text
     *
     * @param string $text Text to search
     * @return string|null Effective date in Y-m-d format or null if not found
     */
    private function extract_effective_date($text) {
        // Common date formats for effective dates
        $date_patterns = array(
            // MM/DD/YYYY
            '/effective\s+(?:date|on|from)?\s*:?\s*(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/i',
            '/(?:starting|begins|beginning|commencing|from)\s+(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/i',
            
            // Month DD, YYYY
            '/effective\s+(?:date|on|from)?\s*:?\s*([A-Za-z]+)\s+(\d{1,2})(?:st|nd|rd|th)?,\s*(\d{4})/i',
            '/(?:starting|begins|beginning|commencing|from)\s+([A-Za-z]+)\s+(\d{1,2})(?:st|nd|rd|th)?,\s*(\d{4})/i',
            
            // DD Month YYYY
            '/effective\s+(?:date|on|from)?\s*:?\s*(\d{1,2})(?:st|nd|rd|th)?\s+([A-Za-z]+)\s+(\d{4})/i',
            '/(?:starting|begins|beginning|commencing|from)\s+(\d{1,2})(?:st|nd|rd|th)?\s+([A-Za-z]+)\s+(\d{4})/i'
        );
        
        foreach ($date_patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // Check which format was matched
                if (is_numeric($matches[1]) && is_numeric($matches[2]) && is_numeric($matches[3])) {
                    // MM/DD/YYYY format
                    $month = $matches[1];
                    $day = $matches[2];
                    $year = $matches[3];
                    return date('Y-m-d', strtotime("$year-$month-$day"));
                } elseif (is_numeric($matches[2]) && is_numeric($matches[3])) {
                    // Month DD, YYYY format
                    $month = date('m', strtotime($matches[1]));
                    $day = $matches[2];
                    $year = $matches[3];
                    return date('Y-m-d', strtotime("$year-$month-$day"));
                } elseif (is_numeric($matches[1]) && is_numeric($matches[3])) {
                    // DD Month YYYY format
                    $day = $matches[1];
                    $month = date('m', strtotime($matches[2]));
                    $year = $matches[3];
                    return date('Y-m-d', strtotime("$year-$month-$day"));
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extract renewal date from text
     *
     * @param string $text Text to search
     * @return string|null Renewal date in Y-m-d format or null if not found
     */
    private function extract_renewal_date($text) {
        // Common date formats for renewal dates
        $date_patterns = array(
            // MM/DD/YYYY
            '/(?:renew|renewal|renews|renewed|auto.?renew|automatically\s+renew)\s+(?:on|date)?\s*:?\s*(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/i',
            
            // Month DD, YYYY
            '/(?:renew|renewal|renews|renewed|auto.?renew|automatically\s+renew)\s+(?:on|date)?\s*:?\s*([A-Za-z]+)\s+(\d{1,2})(?:st|nd|rd|th)?,\s*(\d{4})/i',
            
            // DD Month YYYY
            '/(?:renew|renewal|renews|renewed|auto.?renew|automatically\s+renew)\s+(?:on|date)?\s*:?\s*(\d{1,2})(?:st|nd|rd|th)?\s+([A-Za-z]+)\s+(\d{4})/i'
        );
        
        foreach ($date_patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // Check which format was matched
                if (is_numeric($matches[1]) && is_numeric($matches[2]) && is_numeric($matches[3])) {
                    // MM/DD/YYYY format
                    $month = $matches[1];
                    $day = $matches[2];
                    $year = $matches[3];
                    return date('Y-m-d', strtotime("$year-$month-$day"));
                } elseif (is_numeric($matches[2]) && is_numeric($matches[3])) {
                    // Month DD, YYYY format
                    $month = date('m', strtotime($matches[1]));
                    $day = $matches[2];
                    $year = $matches[3];
                    return date('Y-m-d', strtotime("$year-$month-$day"));
                } elseif (is_numeric($matches[1]) && is_numeric($matches[3])) {
                    // DD Month YYYY format
                    $day = $matches[1];
                    $month = date('m', strtotime($matches[2]));
                    $year = $matches[3];
                    return date('Y-m-d', strtotime("$year-$month-$day"));
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extract payment date from text
     *
     * @param string $text Text to search
     * @return string|null Payment date in Y-m-d format or null if not found
     */
    private function extract_payment_date($text) {
        // Common date formats for payment dates
        $date_patterns = array(
            // MM/DD/YYYY
            '/(?:payment|transaction|paid)\s+(?:date|on)?\s*:?\s*(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/i',
            
            // Month DD, YYYY
            '/(?:payment|transaction|paid)\s+(?:date|on)?\s*:?\s*([A-Za-z]+)\s+(\d{1,2})(?:st|nd|rd|th)?,\s*(\d{4})/i',
            
            // DD Month YYYY
            '/(?:payment|transaction|paid)\s+(?:date|on)?\s*:?\s*(\d{1,2})(?:st|nd|rd|th)?\s+([A-Za-z]+)\s+(\d{4})/i'
        );
        
        foreach ($date_patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // Check which format was matched
                if (is_numeric($matches[1]) && is_numeric($matches[2]) && is_numeric($matches[3])) {
                    // MM/DD/YYYY format
                    $month = $matches[1];
                    $day = $matches[2];
                    $year = $matches[3];
                    return date('Y-m-d', strtotime("$year-$month-$day"));
                } elseif (is_numeric($matches[2]) && is_numeric($matches[3])) {
                    // Month DD, YYYY format
                    $month = date('m', strtotime($matches[1]));
                    $day = $matches[2];
                    $year = $matches[3];
                    return date('Y-m-d', strtotime("$year-$month-$day"));
                } elseif (is_numeric($matches[1]) && is_numeric($matches[3])) {
                    // DD Month YYYY format
                    $day = $matches[1];
                    $month = date('m', strtotime($matches[2]));
                    $year = $matches[3];
                    return date('Y-m-d', strtotime("$year-$month-$day"));
                }
            }
        }
        
        return null;
    }
}