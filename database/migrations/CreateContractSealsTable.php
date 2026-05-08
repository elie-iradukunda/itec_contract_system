<?php

use Core\Migration;

class CreateContractSealsTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS contract_seals (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                applied_by INT NOT NULL,
                seal_image_path VARCHAR(500) NOT NULL,
                approval_code VARCHAR(50) NOT NULL,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                FOREIGN KEY (applied_by) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS contract_seals");
    }
}