<?php

use Core\Migration;

class CreateDocSignatureAuditTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_signature_audit (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                signer_id VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                doc_hash VARCHAR(255) NOT NULL,
                event_type ENUM('signature_created', 'signature_verified', 'tamper_detected', 'seal_applied') NOT NULL,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                INDEX idx_contract_event (contract_id, event_type),
                INDEX idx_timestamp (timestamp)
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS doc_signature_audit");
    }
}