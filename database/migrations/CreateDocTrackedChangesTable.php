<?php

use Core\Migration;

class CreateDocTrackedChangesTable extends Migration
{
    public function up()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_tracked_changes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                doc_id INT NOT NULL,
                author_id INT NULL,
                original_text LONGTEXT NOT NULL,
                new_text LONGTEXT NOT NULL,
                status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
                reviewed_by INT NULL,
                reviewed_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (doc_id) REFERENCES contracts(id) ON DELETE CASCADE,
                FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX doc_tracked_changes_doc_id_index (doc_id),
                INDEX doc_tracked_changes_author_id_index (author_id),
                INDEX doc_tracked_changes_status_index (status)
            )
        ");
    }

    public function down()
    {
        $this->db->exec("DROP TABLE IF EXISTS doc_tracked_changes");
    }
}
