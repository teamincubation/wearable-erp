<?php
/**
 * Wearable ERP SaaS General Configuration
 * Lead Architect - Antigravity
 */

define('APP_NAME', 'Wearable ERP');
define('APP_ENV', 'development'); // 'development' or 'production'
define('APP_DEBUG', true);
define('APP_KEY', 'base64:75wE63wZt75oGq4KWwiNfLec5x9f4aHw8Gvq4Z4XpP0='); // Encryption key

// Session configuration
define('SESSION_LIFETIME', 7200); // 2 hours
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
           (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
define('SESSION_SECURE', $isHttps);
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Lax');

// Domain settings for multi-tenancy
// The main portal where developers log in
define('DEV_PORTAL_HOST', 'erp.mywellgro.online');
define('ROOT_DOMAIN', 'mywellgro.online');

// Active Timezone
date_default_timezone_set('Asia/Kolkata');

function base_url($path = '') {
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';

    // Tenant-aware URL generation to prevent subdomain escape
    if (class_exists('\App\Core\Session')) {
        $isDev = \App\Core\Session::get('is_developer_session');
        if (\App\Core\Session::has('current_tenant') && !$isDev) {
            $tenant = \App\Core\Session::get('current_tenant');
            if (!empty($tenant['subdomain']) && defined('ROOT_DOMAIN')) {
                $expectedHost = $tenant['subdomain'] . '.' . ROOT_DOMAIN;
                if ($host !== $expectedHost) {
                    $host = $expectedHost;
                }
            }
        } elseif ($isDev && !\App\Core\Session::has('company_id')) {
            if (defined('DEV_PORTAL_HOST') && $host !== DEV_PORTAL_HOST) {
                $host = DEV_PORTAL_HOST;
            }
        }
    }

    return $protocol . $host . '/' . ltrim($path, '/');
}

// Global currency symbol mapping resolver helper
function get_currency_symbol() {
    $tenant = \App\Core\Session::get('current_tenant');
    $currency = $tenant['currency'] ?? 'INR';
    $symbols = [
        'INR' => '₹',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'AED' => 'AED',
        'SGD' => 'S$',
        'AUD' => 'A$'
    ];
    return $symbols[$currency] ?? $currency;
}
