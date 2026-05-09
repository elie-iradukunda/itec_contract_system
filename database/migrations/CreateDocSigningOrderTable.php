<?php

use Core\Migration;

class CreateDocSigningOrderTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_signing_order (
                id INT AUTO_INCREMENT PRIMARY KEY,
                doc_id INT NOT NULL,
                party_role VARCHAR(80) NOT NULL,
                user_id INT NULL,
                order_no INT NOT NULL,
                signing_state VARCHAR(50) NOT NULL DEFAULT 'PENDING',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (doc_id) REFERENCES contracts(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                UNIQUE KEY doc_signing_order_doc_order_unique (doc_id, order_no),
                INDEX doc_signing_order_doc_id_index (doc_id),
                INDEX doc_signing_order_user_id_index (user_id),
                INDEX doc_signing_order_signing_state_index (signing_state)
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS doc_signing_order");
    }
}
