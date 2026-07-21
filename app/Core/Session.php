<?php
namespace App\Core;

/**
 * Secure Session Manager and CSRF Protection Handler
 * DevOps & Security Engineer - Antigravity
 */
class Session {
    /**
     * Start a secure session with security options
     */
    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Configure cookie parameters for security
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path' => '/',
                'domain' => '', // Empty dynamically defaults to current host
                'secure' => SESSION_SECURE,
                'httponly' => SESSION_HTTPONLY,
                'samesite' => SESSION_SAMESITE
            ]);

            session_start();
        }

        // Auto-regenerate session periodically to prevent session fixation (e.g., every 30 minutes)
        if (!isset($_SESSION['last_regeneration'])) {
            self::regenerate();
        } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
            self::regenerate();
        }
    }

    /**
     * Regenerate session ID and update timestamp
     */
    public static function regenerate(): void {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }

    /**
     * Set a session key
     */
    public static function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session key
     */
    public static function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session key exists
     */
    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove key from session
     */
    public static function remove(string $key): void {
        unset($_SESSION[$key]);
    }

    /**
     * Destroy session entirely
     */
    public static function destroy(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();
        }
    }

    /**
     * Get or generate a CSRF token
     */
    public static function csrfToken(): string {
        if (!self::has('csrf_token')) {
            $token = bin2hex(random_bytes(32));
            self::set('csrf_token', $token);
        }
        return self::get('csrf_token');
    }

    /**
     * Validate a CSRF token
     */
    public static function validateCsrf(?string $token): bool {
        if (!$token || !self::has('csrf_token')) {
            return false;
        }
        return hash_equals(self::get('csrf_token'), $token);
    }

    /**
     * Generate HTML input field containing the CSRF token
     */
    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Set a flash message
     */
    public static function setFlash(string $type, string $message): void {
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        $_SESSION['flash'][$type] = $message;
    }

    /**
     * Get and clear flash message
     */
    public static function getFlash(string $type): ?string {
        if (isset($_SESSION['flash'][$type])) {
            $message = $_SESSION['flash'][$type];
            unset($_SESSION['flash'][$type]);
            return $message;
        }
        return null;
    }

    /**
     * Check if a flash message of a specific type exists
     */
    public static function hasFlash(string $type): bool {
        return isset($_SESSION['flash'][$type]);
    }
}
