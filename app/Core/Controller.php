<?php
namespace App\Core;

/**
 * Base MVC Controller
 * Full Stack PHP Engineer - Antigravity
 */
class Controller {
    protected Request $request;
    protected Response $response;

    public function __construct(Request $request, Response $response) {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Render a view file with an optional layout
     */
    public function renderView(string $view, array $params = [], string $layout = 'main'): void {
        // Extract parameters to local variables
        foreach ($params as $key => $value) {
            $$key = $value;
        }

        // Render Page View Content into buffer
        ob_start();
        $viewFile = dirname(__DIR__) . "/Views/{$view}.php";
        if (file_exists($viewFile)) {
            include_once $viewFile;
        } else {
            echo "View file [{$view}] not found at {$viewFile}";
        }
        $content = ob_get_clean();

        // Render Layout View if layout exists, else output raw content
        $layoutFile = dirname(__DIR__) . "/Views/layouts/{$layout}.php";
        if (file_exists($layoutFile)) {
            include_once $layoutFile;
        } else {
            echo $content;
        }
        exit;
    }

    /**
     * Send a JSON response helper
     */
    protected function json(array $data, int $code = 200): void {
        $this->response->json($data, $code);
    }

    /**
     * Redirect helper
     */
    protected function redirect(string $url): void {
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            $this->response->redirect($url);
        } else {
            $this->response->redirect(base_url($url));
        }
    }
}
