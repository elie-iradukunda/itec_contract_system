<?php

namespace Core;

class Controller
{
    protected $container;

    public function __construct($container = null)
    {
        $this->container = $container;
    }

    protected function view($view, $data = [])
    {
        extract($data);
        $viewPath = __DIR__ . "/../views/{$view}.php";
        
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            throw new \Exception("View not found: {$view}");
        }
    }

    protected function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
