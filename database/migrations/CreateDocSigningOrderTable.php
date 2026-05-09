<?php

use Core\Migration;

class CreateDocSigningOrderTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_signing_order (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                party_role ENUM('client', 'company_rep') NOT NULL,
                user_id VARCHAR(255) NOT NULL,
                order_no INT NOT NULL,
                signing_state ENUM('pending', 'completed', 'skipped') DEFAULT 'pending',
                signed_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                UNIQUE KEY unique_contract_order (contract_id, order_no),
                INDEX idx_contract_role (contract_id, party_role)
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS doc_signing_order");
    }
}