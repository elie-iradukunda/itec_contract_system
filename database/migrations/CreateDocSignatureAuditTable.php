<?php

use Core\Migration;

class CreateDocSignatureAuditTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_signature_audit (
                id INT AUTO_INCREMENT PRIMARY KEY,
                doc_id INT NOT NULL,
                signer_id INT NULL,
                ip VARCHAR(45) NULL,
                user_agent TEXT NULL,
                doc_hash VARCHAR(255) NOT NULL,
                event_type VARCHAR(80) NOT NULL,
                `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (doc_id) REFERENCES contracts(id) ON DELETE CASCADE,
                FOREIGN KEY (signer_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX doc_signature_audit_doc_id_index (doc_id),
                INDEX doc_signature_audit_signer_id_index (signer_id),
                INDEX doc_signature_audit_event_type_index (event_type),
                INDEX doc_signature_audit_timestamp_index (`timestamp`)
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS doc_signature_audit");
    }
}
