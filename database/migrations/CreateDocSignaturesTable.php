<?php

use Core\Migration;

class CreateDocSignaturesTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_signatures (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                signer_id INT NOT NULL,
                signer_role ENUM('client', 'company_rep') NOT NULL,
                signature_blob TEXT NOT NULL,
                public_key TEXT NOT NULL,
                doc_hash VARCHAR(255) NOT NULL,
                signature_algorithm VARCHAR(50) DEFAULT 'SHA256',
                signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                INDEX idx_contract_signer (contract_id, signer_id),
                INDEX idx_contract_hash (contract_id, doc_hash),
                INDEX idx_signed_at (signed_at)
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS doc_signatures");
    }
}