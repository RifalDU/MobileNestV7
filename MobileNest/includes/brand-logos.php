<?php
/**
 * Brand Logo Configuration
 * Uses high-quality, reliable CDN sources for brand logos
 */

$brand_logos = [
    'Apple' => [
        'image_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@latest/icons/apple.svg',
        'alt' => 'Apple Logo'
    ],
    'Samsung' => [
        'image_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@latest/icons/samsung.svg',
        'alt' => 'Samsung Logo'
    ],
    'Xiaomi' => [
        'image_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@latest/icons/xiaomi.svg',
        'alt' => 'Xiaomi Logo'
    ],
    'OPPO' => [
        'image_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@latest/icons/oppo.svg',
        'alt' => 'OPPO Logo'
    ],
    'Vivo' => [
        'image_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@latest/icons/vivo.svg',
        'alt' => 'Vivo Logo'
    ],
    'Realme' => [
        // Use Direct PNG from reliable source - Realme's official CDN
        'image_url' => 'https://static.realme.com/realme_com/_next/static/image/public/images/logo.8b4c0f17.png',
        'alt' => 'Realme Logo',
        'fallback_urls' => [
            'https://cdn.jsdelivr.net/npm/simple-icons@latest/icons/smartphone.svg'
        ]
    ]
];

/**
 * Get brand logo URL
 * @param string $brand_name - The name of the phone brand
 * @return string - The CDN URL of the logo
 */
function get_brand_logo_url($brand_name) {
    global $brand_logos;
    
    if (isset($brand_logos[$brand_name]['image_url'])) {
        return $brand_logos[$brand_name]['image_url'];
    }
    
    return 'https://cdn.jsdelivr.net/npm/simple-icons@latest/icons/smartphone.svg';
}

/**
 * Get brand logo HTML with fallback support
 * @param string $brand_name - The name of the phone brand
 * @param array $attributes - Additional HTML attributes (class, style, etc)
 * @return string - HTML img tag with logo and onerror fallback
 */
function get_brand_logo_html($brand_name, $attributes = []) {
    global $brand_logos;
    
    $logo_url = get_brand_logo_url($brand_name);
    $alt_text = isset($brand_logos[$brand_name]['alt']) ? $brand_logos[$brand_name]['alt'] : 'Brand Logo';
    
    // Get fallback URLs if available
    $fallback_urls = isset($brand_logos[$brand_name]['fallback_urls']) ? $brand_logos[$brand_name]['fallback_urls'] : [];
    
    // Default attributes
    $default_class = 'brand-logo';
    $class = isset($attributes['class']) ? $attributes['class'] : $default_class;
    $style = isset($attributes['style']) ? $attributes['style'] : 'width: 50px; height: 50px;';
    
    // Build onerror handler for fallback sources
    $onerror_parts = [];
    foreach ($fallback_urls as $url) {
        $onerror_parts[] = "this.src='" . htmlspecialchars($url) . "'";
    }
    $onerror = implode(';', $onerror_parts) . ";if(this.src===this.dataset.lastSrc)this.style.display='none';";
    
    return sprintf(
        '<img src="%s" alt="%s" class="%s" style="%s" loading="lazy" onerror="%s" data-last-src="%s">',
        htmlspecialchars($logo_url),
        htmlspecialchars($alt_text),
        htmlspecialchars($class),
        htmlspecialchars($style),
        $onerror,
        htmlspecialchars(end($fallback_urls) ?: $logo_url)
    );
}

/**
 * Get all available brands
 * @return array - Array of brand names
 */
function get_all_brands() {
    global $brand_logos;
    return array_keys($brand_logos);
}

/**
 * Get brand logo array data
 * @param string $brand_name - The name of the phone brand
 * @return array|null - Array with 'image_url', 'alt' or null if not found
 */
function get_brand_logo_data($brand_name) {
    global $brand_logos;
    return isset($brand_logos[$brand_name]) ? $brand_logos[$brand_name] : null;
}

/**
 * Get brand logo with visual fallback (initials in circle)
 * @param string $brand_name - The name of the phone brand
 * @param string $fallback_color - Fallback background color (hex)
 * @return string - HTML with logo or styled text fallback
 */
function get_brand_logo_with_visual_fallback($brand_name, $fallback_color = '#f0f0f0') {
    global $brand_logos;
    
    $logo_data = get_brand_logo_data($brand_name);
    
    if (!$logo_data) {
        return sprintf(
            '<div class="brand-logo-fallback" style="width: 50px; height: 50px; background-color: %s; border-radius: 50%%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #666; font-size: 12px; flex-shrink: 0;">%s</div>',
            htmlspecialchars($fallback_color),
            htmlspecialchars(substr($brand_name, 0, 2))
        );
    }
    
    $logo_url = $logo_data['image_url'];
    $alt_text = $logo_data['alt'];
    $initials = substr($brand_name, 0, 2);
    $fallback_urls = isset($logo_data['fallback_urls']) ? $logo_data['fallback_urls'] : [];
    
    // Build onerror handler
    $onerror_parts = [];
    foreach ($fallback_urls as $url) {
        $onerror_parts[] = "this.src='" . htmlspecialchars($url) . "'";
    }
    $onerror_parts[] = "this.style.display='none'; this.nextElementSibling.style.display='flex'";
    $onerror = implode(';', $onerror_parts) . ";";
    
    return sprintf(
        '<div style="position: relative; width: 50px; height: 50px; flex-shrink: 0;">
            <img src="%s" alt="%s" 
                 style="width: 100%%; height: 100%%; object-fit: contain; display: block;" 
                 loading="lazy" 
                 onerror="%s">
            <div class="brand-logo-fallback" 
                 style="position: absolute; top: 0; left: 0; width: 100%%; height: 100%%; background-color: %s; border-radius: 50%%; display: none; align-items: center; justify-content: center; font-weight: bold; color: #666; font-size: 12px;">
                %s
            </div>
        </div>',
        htmlspecialchars($logo_url),
        htmlspecialchars($alt_text),
        $onerror,
        htmlspecialchars($fallback_color),
        htmlspecialchars($initials)
    );
}
?>