<?php

use Core\Migration;

class AddSnapshotToDocSignatures extends Migration
{
    public function up()
    {
        // Check if column exists before adding
        try {
            $this->db->exec("
                ALTER TABLE doc_signatures 
                ADD COLUMN snapshot_file_path VARCHAR(500) NULL AFTER signature_file_path
            ");
        } catch (PDOException $e) {
            echo "Note: " . $e->getMessage() . "\n";
        }
    }
    
    public function down()
    {
        $this->db->exec("
            ALTER TABLE doc_signatures 
            DROP COLUMN IF EXISTS snapshot_file_path
        ");
    }
}