<?php

use Core\Migration;

class CreateAuditLogsTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                user_id INT NOT NULL,
                action VARCHAR(255) NOT NULL,
                event_type ENUM('create', 'edit', 'version', 'sign', 'seal', 'state_change', 'upload', 'distribute') NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                details JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS audit_logs");
    }
}