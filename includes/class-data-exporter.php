<?php
/**
 * Data Exporter Class
 * Provides functionality to export financial insights in different formats
 */
class FEC_Data_Exporter {
    
    /**
     * Export financial insights to CSV
     *
     * @param int $user_id User ID
     * @param array $filters Optional filters
     * @return string CSV content
     */
    public function export_to_csv($user_id, $filters = array()) {
        // Get insights data
        $insights = $this->get_insights_data($user_id, $filters);
        
        if (empty($insights)) {
            return '';
        }
        
        // Create a temporary file with CSV data
        $csv_file = fopen('php://temp', 'r+');
        
        // Add CSV headers
        fputcsv($csv_file, array(
            'Date',
            'Type',
            'Description',
            'Amount',
            'Due Date',
            'Status',
            'Source'
        ));
        
        // Add data rows
        foreach ($insights as $insight) {
            fputcsv($csv_file, array(
                $insight->created_at,
                $insight->insight_type,
                $insight->description,
                $insight->amount,
                $insight->due_date,
                $insight->status,
                isset($insight->source_data['email_subject']) ? $insight->source_data['email_subject'] : ''
            ));
        }
        
        // Get CSV content
        rewind($csv_file);
        $csv_content = stream_get_contents($csv_file);
        fclose($csv_file);
        
        return $csv_content;
    }
    
    /**
     * Export financial insights to PDF
     *
     * @param int $user_id User ID
     * @param array $filters Optional filters
     * @return string|bool PDF file path or false on failure
     */
    public function export_to_pdf($user_id, $filters = array()) {
        // Check if TCPDF is available
        if (!class_exists('TCPDF')) {
            if (file_exists(WP_PLUGIN_DIR . '/tcpdf/tcpdf.php')) {
                require_once(WP_PLUGIN_DIR . '/tcpdf/tcpdf.php');
            } else {
                return false;
            }
        }
        
        // Get insights data
        $insights = $this->get_insights_data($user_id, $filters);
        
        if (empty($insights)) {
            return false;
        }
        
        // Create PDF object
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Financial Email Client');
        $pdf->SetAuthor('Financial Email Client');
        $pdf->SetTitle('Financial Insights Report');
        $pdf->SetSubject('Financial Insights Report');
        
        // Set default header and footer data
        $pdf->setHeaderData('', 0, 'Financial Insights Report', date('Y-m-d'));
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Add a page
        $pdf->AddPage();
        
        // Get user information
        $user = get_userdata($user_id);
        $username = $user ? $user->display_name : 'User #' . $user_id;
        
        // Write report title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Financial Insights Report for ' . $username, 0, 1, 'C');
        $pdf->Ln(5);
        
        // Write date range if specified in filters
        if (isset($filters['date_from']) || isset($filters['date_to'])) {
            $pdf->SetFont('helvetica', '', 12);
            $date_range = 'Date Range: ';
            if (isset($filters['date_from'])) {
                $date_range .= date('Y-m-d', strtotime($filters['date_from']));
            } else {
                $date_range .= 'All';
            }
            $date_range .= ' to ';
            if (isset($filters['date_to'])) {
                $date_range .= date('Y-m-d', strtotime($filters['date_to']));
            } else {
                $date_range .= 'Present';
            }
            $pdf->Cell(0, 10, $date_range, 0, 1, 'C');
            $pdf->Ln(5);
        }
        
        // Write table headers
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(30, 7, 'Date', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Type', 1, 0, 'C');
        $pdf->Cell(50, 7, 'Description', 1, 0, 'C');
        $pdf->Cell(25, 7, 'Amount', 1, 0, 'C');
        $pdf->Cell(25, 7, 'Due Date', 1, 0, 'C');
        $pdf->Cell(25, 7, 'Status', 1, 1, 'C');
        
        // Write data rows
        $pdf->SetFont('helvetica', '', 9);
        foreach ($insights as $insight) {
            $pdf->Cell(30, 6, date('Y-m-d', strtotime($insight->created_at)), 1, 0, 'L');
            $pdf->Cell(30, 6, ucfirst(str_replace('_', ' ', $insight->insight_type)), 1, 0, 'L');
            $pdf->Cell(50, 6, $insight->description, 1, 0, 'L');
            $pdf->Cell(25, 6, $insight->amount ? '$' . number_format($insight->amount, 2) : '-', 1, 0, 'R');
            $pdf->Cell(25, 6, $insight->due_date ? date('Y-m-d', strtotime($insight->due_date)) : '-', 1, 0, 'L');
            $pdf->Cell(25, 6, ucfirst($insight->status), 1, 1, 'L');
        }
        
        // Add summary statistics
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Summary Statistics', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        
        // Total bills amount
        $total_bills = 0;
        $total_due = 0;
        $upcoming_bills = 0;
        
        foreach ($insights as $insight) {
            if ($insight->insight_type == 'bill_due' && $insight->amount) {
                $total_bills += $insight->amount;
                
                if ($insight->status == 'new' || $insight->status == 'pending') {
                    $total_due += $insight->amount;
                    
                    // Check if due date is within the next 30 days
                    if ($insight->due_date && strtotime($insight->due_date) > time() && strtotime($insight->due_date) < strtotime('+30 days')) {
                        $upcoming_bills += $insight->amount;
                    }
                }
            }
        }
        
        $pdf->Cell(100, 7, 'Total Bills Amount:', 0, 0, 'L');
        $pdf->Cell(0, 7, '$' . number_format($total_bills, 2), 0, 1, 'L');
        
        $pdf->Cell(100, 7, 'Total Amount Due:', 0, 0, 'L');
        $pdf->Cell(0, 7, '$' . number_format($total_due, 2), 0, 1, 'L');
        
        $pdf->Cell(100, 7, 'Upcoming Bills (Next 30 Days):', 0, 0, 'L');
        $pdf->Cell(0, 7, '$' . number_format($upcoming_bills, 2), 0, 1, 'L');
        
        // Generate file path
        $upload_dir = wp_upload_dir();
        $filename = 'financial-insights-' . $user_id . '-' . date('Ymd-His') . '.pdf';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        // Save PDF to file
        // Check if we can write to the directory
        $upload_dir_writable = wp_is_writable($upload_dir['path']);
        if (!$upload_dir_writable) {
            // Try to create the directory
            wp_mkdir_p($upload_dir['path']);
        }
        
        try {
            $pdf->Output($filepath, 'F');
        } catch (Exception $e) {
            error_log('PDF Export Error: ' . $e->getMessage());
            return false;
        }
        
        // Return file path if successful
        if (file_exists($filepath)) {
            return $filepath;
        }
        
        return false;
    }
    
    /**
     * Export financial insights to JSON
     *
     * @param int $user_id User ID
     * @param array $filters Optional filters
     * @return string JSON content
     */
    public function export_to_json($user_id, $filters = array()) {
        // Get insights data
        $insights = $this->get_insights_data($user_id, $filters);
        
        if (empty($insights)) {
            return json_encode(array());
        }
        
        // Format data for JSON
        $json_data = array();
        foreach ($insights as $insight) {
            $json_data[] = array(
                'id' => $insight->id,
                'type' => $insight->insight_type,
                'description' => $insight->description,
                'amount' => $insight->amount,
                'due_date' => $insight->due_date,
                'status' => $insight->status,
                'created_at' => $insight->created_at,
                'source' => isset($insight->source_data) ? $insight->source_data : null
            );
        }
        
        return json_encode($json_data, JSON_PRETTY_PRINT);
    }
    
    /**
     * Get insights data from database
     *
     * @param int $user_id User ID
     * @param array $filters Optional filters
     * @return array Array of insight objects
     */
    private function get_insights_data($user_id, $filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fec_financial_insights';
        $query = "SELECT * FROM $table_name WHERE user_id = %d";
        $query_args = array($user_id);
        
        // Apply filters
        if (isset($filters['type']) && !empty($filters['type'])) {
            $query .= " AND insight_type = %s";
            $query_args[] = $filters['type'];
        }
        
        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $query .= " AND created_at >= %s";
            $query_args[] = date('Y-m-d H:i:s', strtotime($filters['date_from']));
        }
        
        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $query .= " AND created_at <= %s";
            $query_args[] = date('Y-m-d H:i:s', strtotime($filters['date_to']));
        }
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $query .= " AND status = %s";
            $query_args[] = $filters['status'];
        }
        
        // Order by date
        $query .= " ORDER BY created_at DESC";
        
        // Get results
        $insights = $wpdb->get_results($wpdb->prepare($query, $query_args));
        
        // Decode any JSON data in the results
        foreach ($insights as &$insight) {
            if (isset($insight->source) && !empty($insight->source)) {
                $insight->source_data = json_decode($insight->source, true);
            }
        }
        
        return $insights;
    }
    
    /**
     * Handle export request via AJAX
     */
    public function handle_export_request() {
        // Verify nonce
        check_ajax_referer('fec-ajax-nonce', 'nonce');
        
        // Check user permissions
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to export data.', 'financial-email-client')));
            return;
        }
        
        $user_id = get_current_user_id();
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';
        
        // Get filters
        $filters = array();
        if (isset($_POST['type'])) {
            $filters['type'] = sanitize_text_field($_POST['type']);
        }
        if (isset($_POST['date_from'])) {
            $filters['date_from'] = sanitize_text_field($_POST['date_from']);
        }
        if (isset($_POST['date_to'])) {
            $filters['date_to'] = sanitize_text_field($_POST['date_to']);
        }
        if (isset($_POST['status'])) {
            $filters['status'] = sanitize_text_field($_POST['status']);
        }
        
        // Export data in requested format
        switch ($format) {
            case 'csv':
                $csv_content = $this->export_to_csv($user_id, $filters);
                if (empty($csv_content)) {
                    wp_send_json_error(array('message' => __('No data to export.', 'financial-email-client')));
                    return;
                }
                
                $filename = 'financial-insights-' . date('Ymd-His') . '.csv';
                $upload_dir = wp_upload_dir();
                $filepath = $upload_dir['path'] . '/' . $filename;
                
                // Check if we can write to the directory
                $upload_dir_writable = wp_is_writable($upload_dir['path']);
                if (!$upload_dir_writable) {
                    // Try to create the directory
                    wp_mkdir_p($upload_dir['path']);
                }
                
                // Save CSV to file with error handling
                try {
                    if (file_put_contents($filepath, $csv_content) === false) {
                        wp_send_json_error(array('message' => __('Failed to write CSV file.', 'financial-email-client')));
                        return;
                    }
                } catch (Exception $e) {
                    error_log('CSV Export Error: ' . $e->getMessage());
                    wp_send_json_error(array('message' => __('Error creating CSV file.', 'financial-email-client')));
                    return;
                }
                
                wp_send_json_success(array(
                    'message' => __('CSV export successful.', 'financial-email-client'),
                    'download_url' => $upload_dir['url'] . '/' . $filename
                ));
                break;
                
            case 'pdf':
                $pdf_path = $this->export_to_pdf($user_id, $filters);
                if (!$pdf_path) {
                    wp_send_json_error(array('message' => __('PDF export failed. TCPDF may not be available.', 'financial-email-client')));
                    return;
                }
                
                $upload_dir = wp_upload_dir();
                $filename = basename($pdf_path);
                
                wp_send_json_success(array(
                    'message' => __('PDF export successful.', 'financial-email-client'),
                    'download_url' => $upload_dir['url'] . '/' . $filename
                ));
                break;
                
            case 'json':
                $json_content = $this->export_to_json($user_id, $filters);
                if ($json_content == '[]') {
                    wp_send_json_error(array('message' => __('No data to export.', 'financial-email-client')));
                    return;
                }
                
                $filename = 'financial-insights-' . date('Ymd-His') . '.json';
                $upload_dir = wp_upload_dir();
                $filepath = $upload_dir['path'] . '/' . $filename;
                
                // Check if we can write to the directory
                $upload_dir_writable = wp_is_writable($upload_dir['path']);
                if (!$upload_dir_writable) {
                    // Try to create the directory
                    wp_mkdir_p($upload_dir['path']);
                }
                
                // Save JSON to file with error handling
                try {
                    if (file_put_contents($filepath, $json_content) === false) {
                        wp_send_json_error(array('message' => __('Failed to write JSON file.', 'financial-email-client')));
                        return;
                    }
                } catch (Exception $e) {
                    error_log('JSON Export Error: ' . $e->getMessage());
                    wp_send_json_error(array('message' => __('Error creating JSON file.', 'financial-email-client')));
                    return;
                }
                
                wp_send_json_success(array(
                    'message' => __('JSON export successful.', 'financial-email-client'),
                    'download_url' => $upload_dir['url'] . '/' . $filename
                ));
                break;
                
            default:
                wp_send_json_error(array('message' => __('Invalid export format.', 'financial-email-client')));
                break;
        }
    }
}
