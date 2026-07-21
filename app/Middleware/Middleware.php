<?php
namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Base Middleware Interface/Class
 * Full Stack PHP Engineer - Antigravity
 */
abstract class Middleware {
    /**
     * Handle the request middleware
     * @return bool Return true to proceed, false to halt and redirect/respond
     */
    abstract public function handle(Request $request, Response $response, array $params, ?\App\Core\Route $route = null): bool;
}
