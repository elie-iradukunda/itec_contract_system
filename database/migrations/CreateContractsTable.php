<?php

use Core\Migration;

class CreateContractsTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS contracts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                client_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                file_path VARCHAR(500),
                signing_state ENUM('DRAFT', 'AWAITING_CLIENT', 'CLIENT_SIGNED', 'AWAITING_COMPANY', 'FULLY_SIGNED') DEFAULT 'DRAFT',
                client_signed_at TIMESTAMP NULL,
                company_signed_at TIMESTAMP NULL,
                finalized_at TIMESTAMP NULL,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS contracts");
    }
}