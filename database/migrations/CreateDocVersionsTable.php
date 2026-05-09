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
                saved_by INT NULL,
                saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                file_path VARCHAR(500) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY doc_versions_contract_version_unique (contract_id, version_no),
                INDEX doc_versions_contract_id_index (contract_id),
                INDEX doc_versions_saved_by_index (saved_by)
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS doc_versions");
    }
}
