<?php

use Core\Migration;

class CreateDocSignaturesTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_signatures (
                id INT AUTO_INCREMENT PRIMARY KEY,
                doc_id INT NOT NULL,
                signer_id INT NOT NULL,
                signature_blob LONGTEXT NOT NULL,
                public_key TEXT NULL,
                doc_hash VARCHAR(255) NOT NULL,
                signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (doc_id) REFERENCES contracts(id) ON DELETE CASCADE,
                FOREIGN KEY (signer_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX doc_signatures_doc_id_index (doc_id),
                INDEX doc_signatures_signer_id_index (signer_id),
                INDEX doc_signatures_doc_hash_index (doc_hash)
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS doc_signatures");
    }
}
