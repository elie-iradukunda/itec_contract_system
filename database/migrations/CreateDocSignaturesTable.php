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
                signer_id VARCHAR(255) NOT NULL,
                signature_file_path VARCHAR(500) NOT NULL,
                public_key TEXT NOT NULL,
                doc_hash VARCHAR(255) NOT NULL,
                signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                INDEX idx_contract_signer (contract_id, signer_id)
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS doc_signatures");
    }
}