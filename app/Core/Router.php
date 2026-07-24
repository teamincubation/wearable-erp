<?php
namespace App\Core;

/**
 * Enterprise MVC Routing and Middleware Engine
 * Lead Software Architect - Antigravity
 */
class Router {
    protected array $routes = [];
    protected Request $request;
    protected Response $response;

    public function __construct(Request $request, Response $response) {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Register a GET route
     */
    public function get(string $path, array|callable $callback): Route {
        return $this->addRoute('GET', $path, $callback);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, array|callable $callback): Route {
        return $this->addRoute('POST', $path, $callback);
    }

    /**
     * Add a route to the internal list
     */
    protected function addRoute(string $method, string $path, array|callable $callback): Route {
        $route = new Route($method, $path, $callback);
        $this->routes[$method][] = $route;
        return $route;
    }

    /**
     * Resolve the request and execute the controller/action
     */
    public function resolve() {
        $method = $this->request->getMethod();
        $path = $this->request->getPath();

        // Handle CORS Preflight OPTIONS requests globally for API
        if ($method === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
            header('Access-Control-Max-Age: 86400');
            http_response_code(200);
            exit;
        }

        foreach ($this->routes[$method] ?? [] as $route) {
            $params = [];
            if ($route->match($path, $params)) {
                return $this->executeRoute($route, $params);
            }
        }

        // Return a clean 404 response
        $this->response->setStatusCode(404);
        $controller = new Controller($this->request, $this->response);
        return $controller->renderView('errors/404', ['title' => 'Page Not Found']);
    }

    /**
     * Run middlewares and dispatch controller action
     */
    protected function executeRoute(Route $route, array $params) {
        $middlewares = $route->getMiddlewares();

        // Run middleware pipeline
        foreach ($middlewares as $middlewareClass) {
            $middleware = new $middlewareClass();
            $allowed = $middleware->handle($this->request, $this->response, $params, $route);
            if ($allowed === false) {
                return; // Middleware aborted the request (e.g. redirected)
            }
        }

        $callback = $route->getCallback();

        if (is_callable($callback)) {
            return call_user_func_array($callback, [$this->request, $this->response, ...array_values($params)]);
        }

        if (is_array($callback)) {
            $controllerClass = $callback[0];
            $action = $callback[1];

            if (class_exists($controllerClass)) {
                $controller = new $controllerClass($this->request, $this->response);
                if (method_exists($controller, $action)) {
                    return call_user_func_array([$controller, $action], [$this->request, $this->response, ...array_values($params)]);
                }
            }
        }

        // Internal server error if callback is invalid
        $this->response->setStatusCode(500);
        $controller = new Controller($this->request, $this->response);
        return $controller->renderView('errors/500', ['title' => 'Internal Server Error', 'message' => 'Controller or action not found.']);
    }
}

/**
 * Helper class to represent a single Route config
 */
class Route {
    protected string $method;
    protected string $path;
    protected $callback;
    protected array $middlewares = [];
    protected ?string $permission = null;

    public function __construct(string $method, string $path, array|callable $callback) {
        $this->method = $method;
        $this->path = '/' . trim($path, '/');
        $this->callback = $callback;
    }

    public function getCallback() {
        return $this->callback;
    }

    public function getMiddlewares(): array {
        return $this->middlewares;
    }

    public function getPermission(): ?string {
        return $this->permission;
    }

    /**
     * Set a required permission for this route
     */
    public function permission(string $permissionName): self {
        $this->permission = $permissionName;
        return $this;
    }

    /**
     * Add middleware to the route
     */
    public function middleware(string $middlewareClass): self {
        $this->middlewares[] = $middlewareClass;
        return $this;
    }

    /**
     * Check if the route path matches the requested path and extract dynamic parameters
     */
    public function match(string $requestedPath, array &$params): bool {
        $requestedPath = '/' . trim($requestedPath, '/');

        // Fast match for exact matches
        if ($this->path === $requestedPath) {
            return true;
        }

        // Build pattern regex for variables like {id}
        // e.g. /user/edit/{id} -> /^\/user\/edit\/([^\/]+)$/
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $this->path);
        $pattern = '#^' . str_replace('#', '\#', $pattern) . '$#';

        if (preg_match($pattern, $requestedPath, $matches)) {
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }
}
