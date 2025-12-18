<?php

class WebScraper {
    private $userAgent;
    private $timeout;
    private $followRedirects;
    private $maxRedirects;
    private $cookies = [];
    
    public function __construct() {
        $this->userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';
        $this->timeout = 30;
        $this->followRedirects = true;
        $this->maxRedirects = 5;
    }
    
    /**
     * Get random realistic user agent
     */
    public function getRandomUserAgent() {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:122.0) Gecko/20100101 Firefox/122.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36'
        ];
        return $userAgents[array_rand($userAgents)];
    }
    
    /**
     * Set custom user agent
     */
    public function setUserAgent($userAgent) {
        $this->userAgent = $userAgent;
        return $this;
    }
    
    /**
     * Set timeout in seconds
     */
    public function setTimeout($seconds) {
        $this->timeout = $seconds;
        return $this;
    }
    
    /**
     * Enable/disable following redirects
     */
    public function setFollowRedirects($follow) {
        $this->followRedirects = $follow;
        return $this;
    }
    
    /**
     * Fetch HTML content from URL
     */
    public function fetchHtml($url, $options = []) {
        $ch = curl_init();
        
        // Use random user agent if not specified
        $userAgent = $this->userAgent;
        if (!isset($options['use_same_agent']) || !$options['use_same_agent']) {
            $userAgent = $this->getRandomUserAgent();
        }
        
        // Enhanced default headers to mimic real browser
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language: en-US,en;q=0.9',
            'Accept-Encoding: gzip, deflate',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1',
            'Cache-Control: max-age=0',
            'DNT: 1'
        ];
        
        // Merge custom headers
        if (isset($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
        }
        
        // Add referer if provided
        if (isset($options['referer'])) {
            $headers[] = 'Referer: ' . $options['referer'];
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => $this->followRedirects,
            CURLOPT_MAXREDIRS => $this->maxRedirects,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => 'gzip, deflate',
            CURLOPT_COOKIEJAR => sys_get_temp_dir() . '/scraper_cookies.txt',
            CURLOPT_COOKIEFILE => sys_get_temp_dir() . '/scraper_cookies.txt',
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_AUTOREFERER => true
        ]);
        
        // Add POST data if provided
        if (isset($options['post_data'])) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['post_data']);
        }
        
        // Add custom method if provided
        if (isset($options['method'])) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $options['method']);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        if ($httpCode >= 400) {
            // If 403/429, provide helpful message
            if ($httpCode == 403) {
                throw new Exception("HTTP 403 Forbidden - Website is blocking automated requests. Try using a different URL or adding referer header.");
            } else if ($httpCode == 429) {
                throw new Exception("HTTP 429 Too Many Requests - Rate limited. Wait before retrying.");
            } else if ($httpCode == 503) {
                throw new Exception("HTTP 503 Service Unavailable - Website may be using anti-bot protection (Cloudflare, etc).");
            } else {
                throw new Exception("HTTP Error: " . $httpCode);
            }
        }
        
        return $response;
    }
    
    /**
     * Fetch and parse HTML into DOMDocument
     */
    public function fetchDom($url, $options = []) {
        $html = $this->fetchHtml($url, $options);
        
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        return $dom;
    }
    
    /**
     * Extract all links from HTML
     */
    public function extractLinks($html, $baseUrl = '') {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        
        $links = [];
        $anchors = $dom->getElementsByTagName('a');
        
        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            if ($href) {
                // Convert relative URLs to absolute
                if ($baseUrl && !parse_url($href, PHP_URL_SCHEME)) {
                    $href = rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
                }
                $links[] = [
                    'url' => $href,
                    'text' => trim($anchor->textContent)
                ];
            }
        }
        
        return $links;
    }
    
    /**
     * Extract all images from HTML
     */
    public function extractImages($html, $baseUrl = '') {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        
        $images = [];
        $imgs = $dom->getElementsByTagName('img');
        
        foreach ($imgs as $img) {
            $src = $img->getAttribute('src');
            if ($src) {
                // Convert relative URLs to absolute
                if ($baseUrl && !parse_url($src, PHP_URL_SCHEME)) {
                    $src = rtrim($baseUrl, '/') . '/' . ltrim($src, '/');
                }
                $images[] = [
                    'src' => $src,
                    'alt' => $img->getAttribute('alt'),
                    'title' => $img->getAttribute('title')
                ];
            }
        }
        
        return $images;
    }
    
    /**
     * Extract text content from specific selector using XPath
     */
    public function extractBySelector($html, $xpath) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        
        $domXpath = new DOMXPath($dom);
        $elements = $domXpath->query($xpath);
        
        $results = [];
        foreach ($elements as $element) {
            $results[] = trim($element->textContent);
        }
        
        return $results;
    }
    
    /**
     * Extract meta tags
     */
    public function extractMetaTags($html) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        
        $metas = [];
        $metaTags = $dom->getElementsByTagName('meta');
        
        foreach ($metaTags as $meta) {
            $name = $meta->getAttribute('name') ?: $meta->getAttribute('property');
            $content = $meta->getAttribute('content');
            
            if ($name && $content) {
                $metas[$name] = $content;
            }
        }
        
        return $metas;
    }
    
    /**
     * Get page title
     */
    public function extractTitle($html) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        
        $titles = $dom->getElementsByTagName('title');
        if ($titles->length > 0) {
            return trim($titles->item(0)->textContent);
        }
        
        return '';
    }
    
    /**
     * Download file from URL
     */
    public function downloadFile($url, $savePath) {
        $ch = curl_init($url);
        $fp = fopen($savePath, 'wb');
        
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        curl_exec($ch);
        $error = curl_error($ch);
        
        curl_close($ch);
        fclose($fp);
        
        if ($error) {
            throw new Exception("Download Error: " . $error);
        }
        
        return true;
    }
    
    /**
     * Fetch JSON data from API endpoint
     */
    public function fetchJson($url, $options = []) {
        $html = $this->fetchHtml($url, $options);
        return json_decode($html, true);
    }
}
