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
define('SESSION_SECURE', false);  // Set to true in HTTPS production
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Lax');

// Domain settings for multi-tenancy
// The main portal where developers log in
define('DEV_PORTAL_HOST', 'erp.mywellgro.online');
define('ROOT_DOMAIN', 'mywellgro.online');

// Active Timezone
date_default_timezone_set('Asia/Kolkata');

// Helper function to get base URL
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    return $protocol . $host . '/' . ltrim($path, '/');
}
