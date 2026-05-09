<?php

namespace Core;

class Router
{
    private $routes = [];
    private $container;
    private $middlewareGroups = [];
    private $currentMiddleware = [];

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

    public function put($uri, $handler)
    {
        $this->routes['PUT'][$uri] = $handler;
    }

    public function delete($uri, $handler)
    {
        $this->routes['DELETE'][$uri] = $handler;
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
            if (class_exists($middleware)) {
                $middlewareInstance = new $middleware();
                
                $result = empty($params) 
                    ? $middlewareInstance->handle() 
                    : $middlewareInstance->handle(...$params);
                
                if ($result !== true && $result !== null) {
                    return $result;
                }
            }
        }
        return true;
    }

    public function dispatch($method, $uri)
    {
        $uri = $this->normalizeUri($uri);

        // Run middleware groups
        foreach ($this->middlewareGroups as $group) {
            $result = $this->runMiddleware($group['middlewares']);
            if ($result !== true) {
                return $result;
            }
        }

        // Find matching route
        $routes = $this->routes[$method] ?? [];
        
        foreach ($routes as $route => $handler) {
            $params = $this->matchRoute($route, $uri);
            
            if ($params !== false) {
                return $this->runHandler($handler, $params);
            }
        }
        
        http_response_code(404);
        echo "404 - Page Not Found";
    }

    private function normalizeUri($uri)
    {
        // Remove query string
        $uri = strtok($uri, '?');
        
        // Remove trailing slash
        $uri = rtrim($uri, '/');
        
        // Default to root
        if (empty($uri)) {
            $uri = '/';
        }
        
        return $uri;
    }

    private function matchRoute($route, $uri)
    {
        // Exact match
        if ($route === $uri) {
            return [];
        }

        // Convert route pattern to regex
        // {id} becomes ([^/]+)
        // {id?} becomes ([^/]*) for optional
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\?}/', '([^/]*)', $route);
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $uri, $matches)) {
            return array_slice($matches, 1);
        }
        
        return false;
    }

    private function runHandler($handler, $params)
    {
        if (is_array($handler)) {
            // Controller method
            $controller = $this->container->resolve($handler[0]);
            $method = $handler[1];
            
            if (method_exists($controller, $method)) {
                return call_user_func_array([$controller, $method], $params);
            } else {
                http_response_code(500);
                echo "Method {$method} not found in controller";
                return false;
            }
        }
        
        // Closure
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }
        
        http_response_code(500);
        echo "Invalid route handler";
        return false;
    }
    
    public function group($attributes, $callback)
    {
        // Store current state
        $previousMiddleware = $this->currentMiddleware;
        
        // Apply group middleware
        if (isset($attributes['middleware'])) {
            $this->currentMiddleware = array_merge($this->currentMiddleware, (array)$attributes['middleware']);
        }
        
        // Call the group callback
        call_user_func($callback, $this);
        
        // Restore previous state
        $this->currentMiddleware = $previousMiddleware;
    }
    
    public function api($callback)
    {
        $this->group(['middleware' => []], $callback);
    }
}