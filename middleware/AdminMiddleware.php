<?php

namespace Middleware;

class AdminMiddleware
{
    public function handle()
    {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /itec_contract_system/auth/login');
            exit;
        }
        
        if ($_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            echo "403 Forbidden - Admin access required";
            exit;
        }
        
        return true;
    }
}