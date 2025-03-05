<?php
/**
 * Financial Analyzer Class
 * Analyzes email content for financial information
 */
class FEC_Financial_Analyzer {
    
    /**
     * Analyze email for financial information
     *
     * @param array $email Email data
     * @return array|false Financial insights or false if none found
     */
    public function analyze_email($email) {
        $insights = array();
        
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
            'credit card statement'
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