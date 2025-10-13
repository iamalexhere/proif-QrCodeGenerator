<?php

/**
 * FaviconService Class
 * 
 * Service for retrieving website favicons/icons for QR code cards.
 * Provides multiple fallback methods to get the best available icon.
 */

class FaviconService {
    
    /** @var array Cache for favicon URLs to avoid repeated API calls */
    private static $faviconCache = [];
    
    /**
     * Get favicon URL for a given website URL
     * 
     * @param string $url The website URL
     * @return string The favicon URL or default icon
     */
    public static function getFaviconUrl($url) {
        if (empty($url)) {
            return self::getDefaultIcon();
        }
        
        // Parse URL to get domain
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return self::getDefaultIcon();
        }
        
        $domain = $parsedUrl['host'];
        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : 'https';
        
        // Try multiple favicon services and methods
        $faviconUrls = [
            // Google's favicon service (most reliable)
            "https://www.google.com/s2/favicons?domain={$domain}&sz=32",
            
            // DuckDuckGo favicon service
            "https://icons.duckduckgo.com/ip3/{$domain}.ico",
            
            // Favicon.io service
            "https://favicons.githubusercontent.com/{$domain}",
            
            // Direct favicon.ico from website
            "{$scheme}://{$domain}/favicon.ico",
            
            // Common favicon paths
            "{$scheme}://{$domain}/favicon.png",
            "{$scheme}://{$domain}/apple-touch-icon.png",
        ];
        
        // Return the first available option (Google's service is most reliable)
        return $faviconUrls[0];
    }
    
    /**
     * Get website icon with domain detection for better icons
     * 
     * @param string $url The website URL
     * @return array Icon data with URL and type
     */
    public static function getWebsiteIcon($url) {
        if (empty($url)) {
            return [
                'url' => self::getDefaultIcon(),
                'type' => 'default',
                'domain' => ''
            ];
        }
        
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return [
                'url' => self::getDefaultIcon(),
                'type' => 'default',
                'domain' => ''
            ];
        }
        
        $domain = $parsedUrl['host'];
        $cleanDomain = str_replace('www.', '', $domain);
        
        // Special handling for popular websites
        $specialIcons = self::getSpecialIcons($cleanDomain);
        if ($specialIcons) {
            return [
                'url' => $specialIcons['url'],
                'type' => 'special',
                'domain' => $cleanDomain,
                'name' => $specialIcons['name']
            ];
        }
        
        // Use Google's favicon service as primary
        return [
            'url' => "https://www.google.com/s2/favicons?domain={$domain}&sz=32",
            'type' => 'favicon',
            'domain' => $cleanDomain
        ];
    }
    
    /**
     * Get special icons for popular websites
     * 
     * @param string $domain Clean domain name
     * @return array|null Special icon data or null
     */
    private static function getSpecialIcons($domain) {
        $specialSites = [
            // Social Media
            'github.com' => [
                'url' => 'https://github.githubassets.com/favicons/favicon.png',
                'name' => 'GitHub'
            ],
            'youtube.com' => [
                'url' => 'https://www.youtube.com/s/desktop/favicon.ico',
                'name' => 'YouTube'
            ],
            'facebook.com' => [
                'url' => 'https://static.xx.fbcdn.net/rsrc.php/yo/r/iRmz9lCMBD2.ico',
                'name' => 'Facebook'
            ],
            'twitter.com' => [
                'url' => 'https://abs.twimg.com/favicons/twitter.2.ico',
                'name' => 'Twitter'
            ],
            'x.com' => [
                'url' => 'https://abs.twimg.com/favicons/twitter.2.ico',
                'name' => 'X (Twitter)'
            ],
            'instagram.com' => [
                'url' => 'https://static.cdninstagram.com/rsrc.php/v3/yt/r/30PrGfR3xhD.ico',
                'name' => 'Instagram'
            ],
            'linkedin.com' => [
                'url' => 'https://static.licdn.com/sc/h/al2o9zrvru7aqj8e1x2rzsrca',
                'name' => 'LinkedIn'
            ],
            'reddit.com' => [
                'url' => 'https://www.redditstatic.com/desktop2x/img/favicon/favicon-32x32.png',
                'name' => 'Reddit'
            ],
            'tiktok.com' => [
                'url' => 'https://lf16-tiktok-common.ttwstatic.com/obj/tiktok-web-common-sg/ies/falcon/_next/static/media/favicon.7b7b7c8e.png',
                'name' => 'TikTok'
            ],
            'discord.com' => [
                'url' => 'https://discord.com/assets/f9bb9c4af2b9c32a2c5ee0014661546d.ico',
                'name' => 'Discord'
            ],
            'whatsapp.com' => [
                'url' => 'https://static.whatsapp.net/rsrc.php/v3/yz/r/ujTY9i_Jhs1.png',
                'name' => 'WhatsApp'
            ],
            'telegram.org' => [
                'url' => 'https://telegram.org/favicon.ico',
                'name' => 'Telegram'
            ],
            
            // Search Engines & Tech
            'google.com' => [
                'url' => 'https://www.google.com/favicon.ico',
                'name' => 'Google'
            ],
            'bing.com' => [
                'url' => 'https://www.bing.com/favicon.ico',
                'name' => 'Bing'
            ],
            'yahoo.com' => [
                'url' => 'https://www.yahoo.com/favicon.ico',
                'name' => 'Yahoo'
            ],
            'duckduckgo.com' => [
                'url' => 'https://duckduckgo.com/favicon.ico',
                'name' => 'DuckDuckGo'
            ],
            
            // Development & Tech
            'stackoverflow.com' => [
                'url' => 'https://cdn.sstatic.net/Sites/stackoverflow/Img/favicon.ico',
                'name' => 'Stack Overflow'
            ],
            'gitlab.com' => [
                'url' => 'https://gitlab.com/assets/favicon-72a2cad5025aa931d6ea56c3201d1f18e68a8cd39788c7c80d5b2b82aa5143ef.png',
                'name' => 'GitLab'
            ],
            'bitbucket.org' => [
                'url' => 'https://bitbucket.org/favicon.ico',
                'name' => 'Bitbucket'
            ],
            'codepen.io' => [
                'url' => 'https://cpwebassets.codepen.io/assets/favicon/favicon-aec34940fbc1a6e787974dcd360f2c6b63348d4b1f4e06c77743096d55480f33.ico',
                'name' => 'CodePen'
            ],
            'jsfiddle.net' => [
                'url' => 'https://jsfiddle.net/favicon.png',
                'name' => 'JSFiddle'
            ],
            
            // E-commerce
            'amazon.com' => [
                'url' => 'https://www.amazon.com/favicon.ico',
                'name' => 'Amazon'
            ],
            'ebay.com' => [
                'url' => 'https://www.ebay.com/favicon.ico',
                'name' => 'eBay'
            ],
            'shopify.com' => [
                'url' => 'https://www.shopify.com/favicon.ico',
                'name' => 'Shopify'
            ],
            'etsy.com' => [
                'url' => 'https://www.etsy.com/favicon.ico',
                'name' => 'Etsy'
            ],
            
            // News & Media
            'wikipedia.org' => [
                'url' => 'https://www.wikipedia.org/static/favicon/wikipedia.ico',
                'name' => 'Wikipedia'
            ],
            'medium.com' => [
                'url' => 'https://miro.medium.com/1*m-R_BkNf1Qjr1YbyOIJY2w.png',
                'name' => 'Medium'
            ],
            'bbc.com' => [
                'url' => 'https://www.bbc.com/favicon.ico',
                'name' => 'BBC'
            ],
            'cnn.com' => [
                'url' => 'https://www.cnn.com/favicon.ico',
                'name' => 'CNN'
            ],
            
            // Productivity & Tools
            'notion.so' => [
                'url' => 'https://www.notion.so/images/favicon.ico',
                'name' => 'Notion'
            ],
            'trello.com' => [
                'url' => 'https://trello.com/favicon.ico',
                'name' => 'Trello'
            ],
            'slack.com' => [
                'url' => 'https://a.slack-edge.com/80588/marketing/img/meta/favicon-32.png',
                'name' => 'Slack'
            ],
            'zoom.us' => [
                'url' => 'https://st1.zoom.us/zoom.ico',
                'name' => 'Zoom'
            ],
            'dropbox.com' => [
                'url' => 'https://cfl.dropboxstatic.com/static/images/favicon-vflUeLeeY.ico',
                'name' => 'Dropbox'
            ],
            'drive.google.com' => [
                'url' => 'https://ssl.gstatic.com/docs/doclist/images/drive_2022q3_32dp.png',
                'name' => 'Google Drive'
            ],
            
            // Indonesian Sites
            'tokopedia.com' => [
                'url' => 'https://assets.tokopedia.net/assets-tokopedia-lite/v2/zeus/kratos/62cb37c4.png',
                'name' => 'Tokopedia'
            ],
            'shopee.co.id' => [
                'url' => 'https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/assets/favicon.ico',
                'name' => 'Shopee'
            ],
            'bukalapak.com' => [
                'url' => 'https://www.bukalapak.com/favicon.ico',
                'name' => 'Bukalapak'
            ],
            'gojek.com' => [
                'url' => 'https://www.gojek.com/favicon.ico',
                'name' => 'Gojek'
            ],
            'grab.com' => [
                'url' => 'https://www.grab.com/favicon.ico',
                'name' => 'Grab'
            ]
        ];
        
        return isset($specialSites[$domain]) ? $specialSites[$domain] : null;
    }
    
    /**
     * Get default icon for unknown/invalid URLs
     * 
     * @return string Default icon URL
     */
    private static function getDefaultIcon() {
        // Return a default web icon (you can replace with your own icon)
        return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIiByeD0iNCIgZmlsbD0iIzQ3NzNkYiIvPgo8cGF0aCBkPSJNMTYgOEMxMS41ODE3IDggOCAxMS41ODE3IDggMTZDOCAyMC40MTgzIDExLjU4MTcgMjQgMTYgMjRDMjAuNDE4MyAyNCAyNCAyMC40MTgzIDI0IDE2QzI0IDExLjU4MTcgMjAuNDE4MyA4IDE2IDhaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTYgMTJDMTMuNzkwOSAxMiAxMiAxMy43OTA5IDEyIDE2QzEyIDE4LjIwOTEgMTMuNzkwOSAyMCAxNiAyMEMxOC4yMDkxIDIwIDIwIDE4LjIwOTEgMjAgMTZDMjAgMTMuNzkwOSAxOC4yMDkxIDEyIDE2IDEyWiIgZmlsbD0iIzQ3NzNkYiIvPgo8L3N2Zz4K';
    }
    
    /**
     * Get domain name from URL for display
     * 
     * @param string $url The website URL
     * @return string Clean domain name
     */
    public static function getDomainName($url) {
        if (empty($url)) {
            return 'Unknown';
        }
        
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return 'Unknown';
        }
        
        $domain = $parsedUrl['host'];
        // Remove www. prefix
        return str_replace('www.', '', $domain);
    }
    
    /**
     * Check if URL is valid
     * 
     * @param string $url The URL to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Get website category based on domain
     * 
     * @param string $domain The domain name
     * @return string Website category
     */
    public static function getWebsiteCategory($domain) {
        $categories = [
            // Social Media
            'github.com' => 'Development',
            'youtube.com' => 'Social Media',
            'facebook.com' => 'Social Media',
            'twitter.com' => 'Social Media',
            'x.com' => 'Social Media',
            'instagram.com' => 'Social Media',
            'linkedin.com' => 'Social Media',
            'reddit.com' => 'Social Media',
            'tiktok.com' => 'Social Media',
            'discord.com' => 'Communication',
            'whatsapp.com' => 'Communication',
            'telegram.org' => 'Communication',
            
            // Search & Tech
            'google.com' => 'Search Engine',
            'bing.com' => 'Search Engine',
            'yahoo.com' => 'Search Engine',
            'duckduckgo.com' => 'Search Engine',
            
            // Development
            'stackoverflow.com' => 'Development',
            'gitlab.com' => 'Development',
            'bitbucket.org' => 'Development',
            'codepen.io' => 'Development',
            'jsfiddle.net' => 'Development',
            
            // E-commerce
            'amazon.com' => 'E-commerce',
            'ebay.com' => 'E-commerce',
            'shopify.com' => 'E-commerce',
            'etsy.com' => 'E-commerce',
            'tokopedia.com' => 'E-commerce',
            'shopee.co.id' => 'E-commerce',
            'bukalapak.com' => 'E-commerce',
            
            // News & Media
            'wikipedia.org' => 'Reference',
            'medium.com' => 'Publishing',
            'bbc.com' => 'News',
            'cnn.com' => 'News',
            
            // Productivity
            'notion.so' => 'Productivity',
            'trello.com' => 'Productivity',
            'slack.com' => 'Communication',
            'zoom.us' => 'Communication',
            'dropbox.com' => 'Cloud Storage',
            'drive.google.com' => 'Cloud Storage',
            
            // Transportation
            'gojek.com' => 'Transportation',
            'grab.com' => 'Transportation'
        ];
        
        return isset($categories[$domain]) ? $categories[$domain] : 'Website';
    }
    
    /**
     * Get icon with enhanced metadata
     * 
     * @param string $url The website URL
     * @return array Enhanced icon data
     */
    public static function getEnhancedIcon($url) {
        $basicIcon = self::getWebsiteIcon($url);
        $domain = self::getDomainName($url);
        $category = self::getWebsiteCategory($domain);
        
        return array_merge($basicIcon, [
            'category' => $category,
            'display_name' => isset($basicIcon['name']) ? $basicIcon['name'] : ucfirst(str_replace('.com', '', $domain))
        ]);
    }
    
    /**
     * Preload popular favicons (for performance optimization)
     * 
     * @return array List of popular favicon URLs for preloading
     */
    public static function getPopularFavicons() {
        return [
            'https://www.google.com/s2/favicons?domain=google.com&sz=32',
            'https://www.google.com/s2/favicons?domain=youtube.com&sz=32',
            'https://www.google.com/s2/favicons?domain=facebook.com&sz=32',
            'https://www.google.com/s2/favicons?domain=twitter.com&sz=32',
            'https://www.google.com/s2/favicons?domain=instagram.com&sz=32',
            'https://www.google.com/s2/favicons?domain=linkedin.com&sz=32',
            'https://www.google.com/s2/favicons?domain=github.com&sz=32',
            'https://www.google.com/s2/favicons?domain=stackoverflow.com&sz=32',
            'https://www.google.com/s2/favicons?domain=amazon.com&sz=32',
            'https://www.google.com/s2/favicons?domain=wikipedia.org&sz=32'
        ];
    }
    
    /**
     * Generate CSS for favicon preloading
     * 
     * @return string CSS link tags for preloading
     */
    public static function generatePreloadCSS() {
        $favicons = self::getPopularFavicons();
        $css = '';
        
        foreach ($favicons as $favicon) {
            $css .= "<link rel=\"preload\" href=\"{$favicon}\" as=\"image\">\n";
        }
        
        return $css;
    }
}
