<?php
/**
 * Cloudflare Bypass Scraper - Pure PHP
 * Attempts to bypass Cloudflare by providing all required Client Hints headers
 */

class CloudflareScraper {
    private $cookieFile;
    private $userAgent;
    
    public function __construct() {
        $this->cookieFile = sys_get_temp_dir() . '/cf_scraper_' . md5($_SERVER['REMOTE_ADDR'] ?? 'local') . '.txt';
        $this->userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';
    }
    
    /**
     * Fetch URL with Cloudflare bypass attempt
     */
    public function fetch($url, $referer = null) {
        $ch = curl_init();
        
        // All the Client Hints headers Cloudflare requires
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Accept-Encoding: gzip, deflate',
            'Cache-Control: max-age=0',
            'Sec-CH-UA: "Not_A Brand";v="8", "Chromium";v="121", "Google Chrome";v="121"',
            'Sec-CH-UA-Mobile: ?0',
            'Sec-CH-UA-Platform: "Windows"',
            'Sec-CH-UA-Platform-Version: "15.0.0"',
            'Sec-CH-UA-Arch: "x86"',
            'Sec-CH-UA-Bitness: "64"',
            'Sec-CH-UA-Full-Version: "121.0.6167.140"',
            'Sec-CH-UA-Full-Version-List: "Not_A Brand";v="8.0.0.0", "Chromium";v="121.0.6167.140", "Google Chrome";v="121.0.6167.140"',
            'Sec-CH-UA-Model: ""',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1',
            'Upgrade-Insecure-Requests: 1',
            'Connection: keep-alive'
        ];
        
        if ($referer) {
            $headers[] = 'Referer: ' . $referer;
            // Change Sec-Fetch-Site when we have a referer
            $headers = array_filter($headers, function($h) {
                return strpos($h, 'Sec-Fetch-Site:') === false;
            });
            $headers[] = 'Sec-Fetch-Site: cross-site';
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_ENCODING => 'gzip, deflate',
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
            CURLOPT_HEADER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        if ($httpCode >= 400) {
            throw new Exception("HTTP Error: " . $httpCode);
        }
        
        return $response;
    }
    
    /**
     * Two-step fetch: visit referer first, then target
     */
    public function fetchWithReferer($url, $refererUrl) {
        // Step 1: Visit referer to establish cookies
        try {
            $this->fetch($refererUrl);
        } catch (Exception $e) {
            // Ignore errors on referer
        }
        
        // Small delay to mimic human behavior
        usleep(500000); // 0.5 seconds
        
        // Step 2: Visit target with referer
        return $this->fetch($url, $refererUrl);
    }
    
    /**
     * Clear cookies
     */
    public function clearCookies() {
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }
}
