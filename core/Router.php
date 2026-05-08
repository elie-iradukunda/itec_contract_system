<?php

namespace Core;

class Router
{
    private $routes = [];
    private $container;
    private $middlewareGroups = [];

    public function __construct($container = null)
    {
        $this->container = $container;
    }

    public function get($uri, $handler)
    {
        $this->routes['GET'][$uri] = $handler;
    }

    public function post($uri, $handler)
    {
        $this->routes['POST'][$uri] = $handler;
    }

    public function middleware($middlewares, $callback)
    {
        $this->middlewareGroups[] = [
            'middlewares' => $middlewares,
            'callback' => $callback
        ];
    }

    private function runMiddleware($middlewares, $params = [])
    {
        foreach ($middlewares as $middleware) {
            $middlewareInstance = new $middleware();
            
            $result = empty($params) 
                ? $middlewareInstance->handle() 
                : $middlewareInstance->handle(...$params);
            
            if ($result !== true && $result !== null) {
                return $result; // Return data from middleware (e.g., readonly flag)
            }
        }
        return true;
    }

    public function dispatch($method, $uri)
    {
        $uri = parse_url($uri, PHP_URL_PATH);

        // Check middleware groups first
        foreach ($this->middlewareGroups as $group) {
        }

        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $params = $this->matchRoute($route, $uri);

            if ($params !== false) {
                return $this->runHandler($handler, $params);
            }
        }
        
        http_response_code(404);
        echo "404 - Page Not Found";
    }

    private function matchRoute($route, $uri)
    {
        // Match normal routes and routes with {id} style parameters.
        if ($route === $uri) {
            return [];
        }

        $pattern = preg_replace('#\\\\\{[^/]+\\\\\}#', '([^/]+)', preg_quote($route, '#'));

        if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
            return array_slice($matches, 1);
        }

        return false;
    }

    private function runHandler($handler, $params)
    {
        // Resolve controller handlers through the container.
        if (is_array($handler)) {
            $controller = $this->container->resolve($handler[0]);
            return call_user_func_array([$controller, $handler[1]], $params);
        }

        return call_user_func_array($handler, $params);
    }
}
