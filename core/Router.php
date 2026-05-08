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
        // Check middleware groups first
        foreach ($this->middlewareGroups as $group) {
        }

        if (isset($this->routes[$method][$uri])) {
            $handler = $this->routes[$method][$uri];
            
            if (is_array($handler)) {
                $controller = $this->container->resolve($handler[0]);
                $methodName = $handler[1];
                return call_user_func([$controller, $methodName]);
            }
            
            return call_user_func($handler);
        }
        
        http_response_code(404);
        echo "404 - Page Not Found";
    }
}