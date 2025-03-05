<?php
/**
 * Email Parser Class
 * Parses email content for better analysis
 */
class FEC_Email_Parser {
    
    /**
     * Parse email content
     *
     * @param string $content Email content
     * @return array Parsed content
     */
    public function parse_content($content) {
        // Basic parsing for proof of concept
        $parsed = array(
            'plain_text' => $this->extract_plain_text($content),
            'links' => $this->extract_links($content),
            'dates' => $this->extract_dates($content),
            'amounts' => $this->extract_amounts($content)
        );
        
        return $parsed;
    }
    
    /**
     * Extract plain text from HTML
     *
     * @param string $html HTML content
     * @return string Plain text
     */
    private function extract_plain_text($html) {
        // Remove HTML tags
        $text = strip_tags($html);
        
        // Decode HTML entities
        $text = html_entity_decode($text);
        
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Extract links from HTML
     *
     * @param string $html HTML content
     * @return array Links
     */
    private function extract_links($html) {
        $links = array();
        
        // Extract href attributes
        if (preg_match_all('/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1/i', $html, $matches)) {
            $links = $matches[2];
        }
        
        return $links;
    }
    
    /**
     * Extract dates from text
     *
     * @param string $text Text content
     * @return array Dates
     */
    private function extract_dates($text) {
        $dates = array();
        
        // Common date formats in emails
        $date_patterns = array(
            // MM/DD/YYYY
            '/(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/',
            
            // Month DD, YYYY
            '/([A-Za-z]+)\s+(\d{1,2})(?:st|nd|rd|th)?,\s*(\d{4})/',
            
            // DD Month YYYY
            '/(\d{1,2})(?:st|nd|rd|th)?\s+([A-Za-z]+)\s+(\d{4})/'
        );
        
        foreach ($date_patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $dates[] = $match[0];
                }
            }
        }
        
        return $dates;
    }
    
    /**
     * Extract monetary amounts from text
     *
     * @param string $text Text content
     * @return array Amounts
     */
    private function extract_amounts($text) {
        $amounts = array();
        
        // Common amount formats in emails
        $amount_patterns = array(
            '/\$(\d+(?:,\d{3})*(?:\.\d{2})?)/',
            '/(\d+(?:,\d{3})*(?:\.\d{2})?)\s*(?:USD|dollars|EUR|GBP)/'
        );
        
        foreach ($amount_patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $amounts[] = $match[0];
                }
            }
        }
        
        return $amounts;
    }
}