<?php

namespace Core;

class Container
{
    private $bindings = [];
    private $instances = [];

    public function bind($key, $callback)
    {
        $this->bindings[$key] = $callback;
    }

    public function singleton($key, $callback)
    {
        $this->bindings[$key] = function($container) use ($callback) {
            static $instance;
            if ($instance === null) {
                $instance = $callback($container);
            }
            return $instance;
        };
    }

    public function resolve($key)
    {
        if (!isset($this->bindings[$key])) {
            throw new \Exception("No binding found for: {$key}");
        }
        return $this->bindings[$key]($this);
    }
}
