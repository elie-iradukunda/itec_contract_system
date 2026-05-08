<?php

namespace Middleware;

use Core\Database;

class SignedContractMiddleware
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function handle($contractId)
    {
        session_start();
        
        $stmt = $this->db->prepare("SELECT signing_state FROM contracts WHERE id = ?");
        $stmt->execute([$contractId]);
        $contract = $stmt->fetch();
        
        if (!$contract) {
            http_response_code(404);
            echo "404 - Contract not found";
            exit;
        }
        
        // Check if contract is fully signed
        $readOnlyStates = ['CLIENT_SIGNED', 'AWAITING_COMPANY', 'FULLY_SIGNED'];
        
        if (in_array($contract['signing_state'], $readOnlyStates)) {
            // Allow access but mark as read-only
            return ['readonly' => true, 'state' => $contract['signing_state']];
        }
        
        return ['readonly' => false, 'state' => $contract['signing_state']];
    }
}