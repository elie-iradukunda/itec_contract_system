<?php

use Core\Migration;

class CreateContractSignaturesTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS contract_signatures (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                signer_id INT NOT NULL,
                signer_role ENUM('client', 'company_rep') NOT NULL,
                signature_blob TEXT NOT NULL,
                public_key TEXT,
                document_hash VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                FOREIGN KEY (signer_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS contract_signatures");
    }
}