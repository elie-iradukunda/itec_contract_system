<?php

use Core\Migration;

class CreateDocTrackedChangesTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_tracked_changes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                author_id VARCHAR(255) NOT NULL,
                original_text TEXT,
                new_text TEXT,
                status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
                changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                INDEX idx_contract_status (contract_id, status)
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS doc_tracked_changes");
    }
}