<?php

use Core\Migration;

class CreateClientsTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS clients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                company_name VARCHAR(255),
                contact_person VARCHAR(255),
                phone VARCHAR(50),
                email VARCHAR(255),
                address TEXT,
                tax_id VARCHAR(100),
                registration_number VARCHAR(100),
                status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS clients");
    }
}