<?php

namespace Middleware;

class GuestMiddleware
{
    public function handle()
    {
        session_start();
        
        if (isset($_SESSION['user_id'])) {
            header('Location: /itec_contract_system/dashboard');
            exit;
        }
        
        return true;
    }
}