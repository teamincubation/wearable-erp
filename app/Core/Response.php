<?php
namespace App\Core;

/**
 * HTTP Response Helper Wrapper
 * Full Stack PHP Engineer - Antigravity
 */
class Response {
    /**
     * Set the HTTP response status code
     */
    public function setStatusCode(int $code): self {
        http_response_code($code);
        return $this;
    }

    /**
     * Return a JSON response
     */
    public function json(array $data, int $code = 200): void {
        if (ob_get_length()) {
            @ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        $this->setStatusCode($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Redirect to another URL and exit
     */
    public function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Send plain/HTML text response and exit
     */
    public function send(string $content): void {
        echo $content;
        exit;
    }
}
