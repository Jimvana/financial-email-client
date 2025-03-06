<?php
/**
 * Email Connector Class
 * Handles connections to email servers via IMAP
 */
class FEC_Email_Connector {
    
    /**
     * Connect to an email server
     *
     * @param string $email Email address
     * @param string $password Password or access token
     * @param string $provider Email provider (gmail, outlook, etc.)
     * @return array|WP_Error Connection details or error
     */
    public function connect($email, $password, $provider) {
        // Server settings for popular providers
        $server_settings = $this->get_provider_settings($provider);
        
        if (is_wp_error($server_settings)) {
            return $server_settings;
        }
        
        // Try to establish connection
        $imap_stream = @imap_open(
            $server_settings['mailbox'],
            $email,
            $password,
            OP_READONLY,
            1,
            array('DISABLE_AUTHENTICATOR' => 'GSSAPI')
        );
        
        if (!$imap_stream) {
            return new WP_Error('connection_failed', imap_last_error());
        }
        
        // Close the connection for now
        imap_close($imap_stream);
        
        // Return connection details
        return array(
            'email' => $email,
            'password' => $password, // Will be encrypted before storage
            'provider' => $provider,
            'server_settings' => $server_settings
        );
    }
    
/**
 * Reconnect to an email account using stored credentials
 *
 * @param object $account Account data from database
 * @return resource|WP_Error IMAP connection or error
 */
public function reconnect($account) {
    // Set a timeout for IMAP connections
    imap_timeout(IMAP_OPENTIMEOUT, 5);
    imap_timeout(IMAP_READTIMEOUT, 5);
    imap_timeout(IMAP_WRITETIMEOUT, 5);
    imap_timeout(IMAP_CLOSETIMEOUT, 5);
    
    // Decrypt connection details
    $encryption_key = get_option('fec_encryption_key');
    $server_settings = json_decode($this->decrypt_data(
        $account->auth_token,
        $account->server_settings,
        $encryption_key
    ), true);
    
    if (empty($server_settings)) {
        return new WP_Error('invalid_settings', __('Invalid server settings.', 'financial-email-client'));
    }
    
    // Debug log
    error_log('Attempting to connect to: ' . $server_settings['server_settings']['mailbox']);
    error_log('Using email: ' . $server_settings['email']);
    
    // Try to establish connection
    $imap_stream = @imap_open(
        $server_settings['server_settings']['mailbox'],
        $server_settings['email'],
        $server_settings['password'],
        OP_READONLY,
        1,
        array('DISABLE_AUTHENTICATOR' => 'GSSAPI')
    );
    
    if (!$imap_stream) {
        $error = imap_last_error();
        error_log('IMAP connection failed: ' . $error);
        return new WP_Error('connection_failed', $error);
    }
    
    return $imap_stream;
}
    
    /**
     * Get provider-specific settings
     *
     * @param string $provider Provider name
     * @return array|WP_Error Server settings or error
     */
private function get_provider_settings($provider) {
    $settings = array();
    
    switch (strtolower($provider)) {
        case 'gmail':
            $settings = array(
                'mailbox' => '{imap.gmail.com:993/imap/ssl}INBOX',
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => 465,
                'smtp_secure' => 'ssl'
            );
            break;
            
        case 'outlook':
        case 'hotmail':
        case 'live':
            $settings = array(
                'mailbox' => '{outlook.office365.com:993/imap/ssl}INBOX',
                'smtp_host' => 'smtp.office365.com',
                'smtp_port' => 587,
                'smtp_secure' => 'tls'
            );
            break;
            
        case 'yahoo':
            $settings = array(
                'mailbox' => '{imap.mail.yahoo.com:993/imap/ssl}INBOX',
                'smtp_host' => 'smtp.mail.yahoo.com',
                'smtp_port' => 465,
                'smtp_secure' => 'ssl'
            );
            break;
            
        default:
            return new WP_Error('invalid_provider', __('Unsupported email provider.', 'financial-email-client'));
    }
    
    return $settings;
}
	
/**
 * Fetch emails from a specific folder
 *
 * @param resource $imap_stream IMAP connection
 * @param string $folder Folder name
 * @param int $page Page number
 * @param int $per_page Emails per page
 * @return array Emails and metadata
 */
/**
 * Fetch emails from a specific folder
 *
 * @param resource $imap_stream IMAP connection
 * @param string $folder Folder name
 * @param int $page Page number
 * @param int $per_page Emails per page
 * @return array Emails and metadata
 */
public function fetch_emails($imap_stream, $folder = 'INBOX', $page = 1, $per_page = 10) {
    try {
        // Log the start of the process
        error_log('Starting to fetch emails from folder: ' . $folder);
        
        // Select the folder
        if (!@imap_reopen($imap_stream, $folder)) {
            error_log('Failed to open folder: ' . $folder . ' - ' . imap_last_error());
            return array(
                'messages' => array(),
                'total' => 0,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => 0,
                'error' => 'Failed to open folder: ' . imap_last_error()
            );
        }
        
        // Get total number of emails
        $total_emails = imap_num_msg($imap_stream);
        error_log('Total emails reported by IMAP: ' . $total_emails);
        
        // If no emails, return empty array
        if ($total_emails === 0) {
            return array(
                'messages' => array(),
                'total' => 0,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => 0
            );
        }
        
        // Calculate pagination properly
        $start = ($page - 1) * $per_page + 1;
        $end = min($start + $per_page - 1, $total_emails);
        
        // Convert to reverse order (newest first)
        $start_rev = max($total_emails - $end + 1, 1);
        $end_rev = $total_emails - $start + 1;
        
        error_log("Calculated start: $start, end: $end");
        error_log("Converted to reverse: start_rev: $start_rev, end_rev: $end_rev");
        
        // Initialize emails array
        $emails = array();
        
        // Fetch emails
        for ($i = $end_rev; $i >= $start_rev; $i--) {
            try {
                $header = @imap_headerinfo($imap_stream, $i);
                
                // Skip if no header
                if (!$header) {
                    error_log("Failed to get header for message #$i: " . imap_last_error());
                    continue;
                }
                
                // Get email subject
                $subject = '';
                if (isset($header->subject)) {
                    $subject = $this->decode_mime_str($header->subject);
                }
                
                // Get sender
                $from = '';
                if (isset($header->from[0]->mailbox) && isset($header->from[0]->host)) {
                    $from = $header->from[0]->mailbox . '@' . $header->from[0]->host;
                }
                
                // Get sender name
                $from_name = '';
                if (isset($header->from[0]->personal)) {
                    $from_name = $this->decode_mime_str($header->from[0]->personal);
                }
                
                // Get date
                $date = date('Y-m-d H:i:s', strtotime($header->date));
                
                // Check if email has attachments
                $structure = @imap_fetchstructure($imap_stream, $i);
                $has_attachments = $this->has_attachments($structure);
                
                // Get message size
                $size = $header->Size;
                
                // Get message UID
                $uid = imap_uid($imap_stream, $i);
                
                // Get message flags
                $flags = array();
                if (isset($header->Flagged) && $header->Flagged) {
                    $flags[] = 'flagged';
                }
                if (isset($header->Answered) && $header->Answered) {
                    $flags[] = 'answered';
                }
                if (isset($header->Seen) && $header->Seen) {
                    $flags[] = 'seen';
                }
                
                // Get preview (limit to reduce processing time)
                $preview = substr($this->get_message_preview($imap_stream, $i), 0, 100);
                
                // Add email to array
                $emails[] = array(
                    'uid' => $uid,
                    'subject' => $subject,
                    'from' => $from,
                    'from_name' => $from_name,
                    'date' => $date,
                    'has_attachments' => $has_attachments,
                    'size' => $size,
                    'flags' => $flags,
                    'preview' => $preview
                );
                
            } catch (Exception $e) {
                error_log("Exception processing message #$i: " . $e->getMessage());
                continue;
            }
        }
        
        return array(
            'messages' => $emails,
            'total' => $total_emails,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_emails / $per_page)
        );
        
    } catch (Exception $e) {
        error_log('Exception in fetch_emails: ' . $e->getMessage());
        return array(
            'messages' => array(),
            'total' => 0,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => 0,
            'error' => $e->getMessage()
        );
    }
}
    /**
     * Get email by ID
     *
     * @param int $user_id User ID
     * @param string $email_id Email UID
     * @return array|WP_Error Email data or error
     */
    public function get_email_by_id($user_id, $email_id) {
        // Get user's email accounts
        global $wpdb;
        $account = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}fec_email_accounts WHERE user_id = %d LIMIT 1",
                $user_id
            )
        );
        
        if (!$account) {
            return new WP_Error('no_account', __('No email account found.', 'financial-email-client'));
        }
        
        // Connect to email account
        $imap_stream = $this->reconnect($account);
        
        if (is_wp_error($imap_stream)) {
            return $imap_stream;
        }
        
        // Find the message number from UID
        $msg_number = imap_msgno($imap_stream, $email_id);
        
        if (!$msg_number) {
            imap_close($imap_stream);
            return new WP_Error('email_not_found', __('Email not found.', 'financial-email-client'));
        }
        
        // Get email headers
        $header = imap_headerinfo($imap_stream, $msg_number);
        
        // Get email structure
        $structure = imap_fetchstructure($imap_stream, $msg_number);
        
        // Get email body
        $body = $this->get_message_body($imap_stream, $msg_number, $structure);
        
        // Get attachments
        $attachments = $this->get_attachments($imap_stream, $msg_number, $structure);
        
        imap_close($imap_stream);
        
        // Format email data
        $email = array(
            'uid' => $email_id,
            'subject' => $this->decode_mime_str($header->subject),
            'from' => $header->from[0]->mailbox . '@' . $header->from[0]->host,
            'from_name' => isset($header->from[0]->personal) ? $this->decode_mime_str($header->from[0]->personal) : '',
            'to' => $this->get_address_list($header->to),
            'cc' => isset($header->cc) ? $this->get_address_list($header->cc) : array(),
            'date' => date('Y-m-d H:i:s', strtotime($header->date)),
            'body' => $body,
            'attachments' => $attachments
        );
        
        return $email;
    }
    
    /**
     * Check if email has attachments
     * 
     * @param object $structure Email structure
     * @return bool True if has attachments
     */
    private function has_attachments($structure) {
        if (isset($structure->parts)) {
            foreach ($structure->parts as $part) {
                if ($part->ifdisposition && strtolower($part->disposition) == 'attachment') {
                    return true;
                }
                
                if (isset($part->parts)) {
                    if ($this->has_attachments($part)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get message preview (first few lines)
     * 
     * @param resource $imap_stream IMAP connection
     * @param int $msg_number Message number
     * @return string Message preview
     */
    private function get_message_preview($imap_stream, $msg_number) {
        // Get plain text body
        $body = imap_fetchbody($imap_stream, $msg_number, 1);
        
        // If no plain text, try HTML body
        if (empty($body)) {
            $body = imap_fetchbody($imap_stream, $msg_number, 2);
        }
        
        // Decode body
        $body = $this->decode_message_body($body);
        
        // Strip HTML tags
        $body = strip_tags($body);
        
        // Trim and return preview
        $preview = substr($body, 0, 200);
        if (strlen($body) > 200) {
            $preview .= '...';
        }
        
        return $preview;
    }
    
    /**
     * Get message body
     * 
     * @param resource $imap_stream IMAP connection
     * @param int $msg_number Message number
     * @param object $structure Message structure
     * @return string Message body
     */
    private function get_message_body($imap_stream, $msg_number, $structure) {
        $body = '';
        $html_body = '';
        
        // If simple structure
        if (!isset($structure->parts) || !$structure->parts) {
            $body = imap_body($imap_stream, $msg_number);
            $body = $this->decode_message_body($body, $structure->encoding);
            
            return $body;
        }
        
        // Complex message with parts
        foreach ($structure->parts as $part_number => $part) {
            $part_number = $part_number + 1;
            
            // Check if this part is body
            if ($part->type == 0) {
                $part_body = imap_fetchbody($imap_stream, $msg_number, $part_number);
                $part_body = $this->decode_message_body($part_body, $part->encoding);
                
                // Check if plain text or HTML
                if ($part->subtype == 'PLAIN') {
                    $body = $part_body;
                } elseif ($part->subtype == 'HTML') {
                    $html_body = $part_body;
                }
            }
        }
        
        // Prefer HTML body if available
        return !empty($html_body) ? $html_body : $body;
    }
    
    /**
     * Get email attachments
     * 
     * @param resource $imap_stream IMAP connection
     * @param int $msg_number Message number
     * @param object $structure Message structure
     * @return array Attachments
     */
    private function get_attachments($imap_stream, $msg_number, $structure) {
        $attachments = array();
        
        if (isset($structure->parts)) {
            foreach ($structure->parts as $part_number => $part) {
                $part_number = $part_number + 1;
                
                // Check if attachment
                if ($part->ifdisposition && strtolower($part->disposition) == 'attachment') {
                    $file_name = '';
                    
                    // Get filename
                    if (isset($part->dparameters[0]->value)) {
                        $file_name = $part->dparameters[0]->value;
                    } elseif (isset($part->parameters[0]->value)) {
                        $file_name = $part->parameters[0]->value;
                    }
                    
                    // Decode filename
                    $file_name = $this->decode_mime_str($file_name);
                    
                    $attachments[] = array(
                        'name' => $file_name,
                        'part_number' => $part_number,
                        'size' => $part->bytes,
                        'type' => $part->subtype
                    );
                }
            }
        }
        
        return $attachments;
    }
    
    /**
     * Get address list from header object
     * 
     * @param object $addresses Address objects
     * @return array Formatted addresses
     */
    private function get_address_list($addresses) {
        $result = array();
        
        foreach ($addresses as $address) {
            $email = $address->mailbox . '@' . $address->host;
            $name = isset($address->personal) ? $this->decode_mime_str($address->personal) : '';
            
            $result[] = array(
                'email' => $email,
                'name' => $name
            );
        }
        
        return $result;
    }
    
    /**
     * Decode MIME encoded string
     * 
     * @param string $string MIME encoded string
     * @return string Decoded string
     */
    private function decode_mime_str($string) {
        $result = '';
        
        if (!$string) {
            return $result;
        }
        
        $elements = imap_mime_header_decode($string);
        
        foreach ($elements as $element) {
            if ($element->charset == 'default') {
                $result .= $element->text;
            } else {
                $result .= iconv($element->charset, 'UTF-8', $element->text);
            }
        }
        
        return $result;
    }
    
    /**
     * Decode message body based on encoding
     * 
     * @param string $body Encoded body
     * @param int $encoding Encoding type
     * @return string Decoded body
     */
    private function decode_message_body($body, $encoding = 0) {
        switch ($encoding) {
            case 3: // BASE64
                $body = base64_decode($body);
                break;
                
            case 4: // QUOTED-PRINTABLE
                $body = quoted_printable_decode($body);
                break;
        }
        
        return $body;
    }
    
    /**
     * Decrypt sensitive data
     */
private function decrypt_data($encrypted, $iv, $key) {
        $method = 'AES-256-CBC';
        return openssl_decrypt(base64_decode($encrypted), $method, $key, 0, base64_decode($iv));
    } // Add this closing brace for the decrypt_data method

} // Add this closing brace for the FEC_Email_Connector class