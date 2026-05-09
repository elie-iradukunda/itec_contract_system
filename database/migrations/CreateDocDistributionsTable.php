<?php

use Core\Migration;

class CreateDocDistributionsTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_distributions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                doc_id INT NOT NULL,
                recipient_email VARCHAR(255) NOT NULL,
                token VARCHAR(128) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                opened_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                sent_at TIMESTAMP NULL,
                FOREIGN KEY (doc_id) REFERENCES contracts(id) ON DELETE CASCADE,
                UNIQUE KEY doc_distributions_token_unique (token),
                INDEX doc_distributions_doc_id_index (doc_id),
                INDEX doc_distributions_recipient_email_index (recipient_email),
                INDEX doc_distributions_expires_at_index (expires_at)
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS doc_distributions");
    }
}
