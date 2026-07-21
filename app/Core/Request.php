<?php
namespace App\Core;

/**
 * HTTP Request Handler and Sanitization Wrapper
 * Full Stack PHP Engineer - Antigravity
 */
class Request {
    /**
     * Get the request method (GET, POST, etc.)
     */
    public function getMethod(): string {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Get the sanitized request URI path
     */
    public function getPath(): string {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }
        return '/' . trim($path, '/');
    }

    /**
     * Check if request is POST
     */
    public function isPost(): bool {
        return $this->getMethod() === 'POST';
    }

    /**
     * Check if request is GET
     */
    public function isGet(): bool {
        return $this->getMethod() === 'GET';
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Fetch all raw and sanitized inputs (GET, POST, JSON)
     */
    public function all(): array {
        $data = [];

        // Parse query params
        foreach ($_GET as $key => $value) {
            $data[$key] = $this->sanitize($value);
        }

        // Parse post fields
        foreach ($_POST as $key => $value) {
            $data[$key] = $this->sanitize($value);
        }

        // Parse JSON inputs
        $inputJSON = file_get_contents('php://input');
        $decoded = json_decode($inputJSON, true);
        if (is_array($decoded)) {
            foreach ($decoded as $key => $value) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }

    /**
     * Get a specific input value with sanitization
     */
    public function get(string $key, mixed $default = null): mixed {
        $all = $this->all();
        return $all[$key] ?? $default;
    }

    /**
     * Sanitize input recursively to prevent XSS
     */
    private function sanitize(mixed $value): mixed {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->sanitize($v);
            }
            return $value;
        }
        if (is_string($value)) {
            return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
        return $value;
    }
}
