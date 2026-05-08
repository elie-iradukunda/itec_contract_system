<?php

use Core\Migration;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                recipient_email VARCHAR(255) NOT NULL,
                token VARCHAR(255),
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                opened_at TIMESTAMP NULL,
                status ENUM('pending', 'sent', 'delivered', 'expired') DEFAULT 'pending',
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS notifications");
    }
}