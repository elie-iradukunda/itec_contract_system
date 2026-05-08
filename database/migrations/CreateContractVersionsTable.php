<?php

use Core\Migration;

class CreateContractVersionsTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS contract_versions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                version_no INT NOT NULL,
                saved_by INT NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                FOREIGN KEY (saved_by) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS contract_versions");
    }
}