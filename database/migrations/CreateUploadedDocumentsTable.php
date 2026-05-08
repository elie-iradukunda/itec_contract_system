<?php

use Core\Migration;

class CreateUploadedDocumentsTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS uploaded_documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                uploaded_by INT NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                file_type ENUM('hard_copy_signed', 'signature_image', 'seal_image', 'final_pdf') NOT NULL,
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS uploaded_documents");
    }
}