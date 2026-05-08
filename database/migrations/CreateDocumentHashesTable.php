<?php

use Core\Migration;

class CreateDocumentHashesTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS document_hashes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                signature_id INT NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                hash_value VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                FOREIGN KEY (signature_id) REFERENCES contract_signatures(id) ON DELETE CASCADE
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS document_hashes");
    }
}