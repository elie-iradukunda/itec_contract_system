<?php

namespace Middleware;

use Core\Database;

class ReadOnlyContractMiddleware
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function handle($contractId)
    {
        session_start();
        
        // Check if contract is in read-only state
        $stmt = $this->db->prepare("SELECT signing_state FROM contracts WHERE id = ?");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();
        
        if ($contract && $contract['signing_state'] !== 'DRAFT') {
            http_response_code(403);
            echo "403 Forbidden - Contract body is frozen. Only signatures can be added.";
            exit;
        }
        
        return true;
    }
}