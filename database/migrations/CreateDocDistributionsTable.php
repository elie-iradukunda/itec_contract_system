<?php

use Core\Migration;

class CreateDocDistributionsTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_distributions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                recipient_email VARCHAR(255) NOT NULL,
                token VARCHAR(255) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                opened_at TIMESTAMP NULL,
                status ENUM('pending', 'sent', 'delivered', 'expired') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                UNIQUE KEY unique_token (token),
                INDEX idx_token_expiry (token, expires_at)
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS doc_distributions");
    }
}
