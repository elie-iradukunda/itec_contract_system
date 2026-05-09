<?php

use Core\Migration;

class CreateDocVersionsTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_versions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                version_no INT NOT NULL,
                saved_by VARCHAR(255) NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                INDEX idx_contract_version (contract_id, version_no),
                UNIQUE KEY unique_contract_version (contract_id, version_no)
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS doc_versions");
    }
}
