<?php

use Core\Migration;

class EnsureContractFlowSchema extends Migration
{
    public function up()
    {
        $this->ensureUsers();
        $this->ensureClients();
        $this->ensureContracts();
        $this->ensureVersions();
        $this->ensureTrackedChanges();
        $this->ensureSignatures();
        $this->ensureSigningOrder();
        $this->ensureAudit();
        $this->ensureDistributions();
        $this->seedDefaultParties();
    }

    public function down()
    {
        // This migration is intentionally non-destructive because it protects live contract records.
    }

    private function ensureUsers()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'staff',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        $this->addColumn('users', 'name', "VARCHAR(255) NULL");
        $this->addColumn('users', 'email', "VARCHAR(255) NULL");
        $this->addColumn('users', 'password', "VARCHAR(255) NULL");
        $this->addColumn('users', 'role', "VARCHAR(50) DEFAULT 'staff'");
    }

    private function ensureClients()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS clients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(50) NULL,
                company_name VARCHAR(255) NULL,
                contact_person VARCHAR(255) NULL,
                address TEXT NULL,
                tax_id VARCHAR(100) NULL,
                registration_number VARCHAR(100) NULL,
                status VARCHAR(30) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        $this->addColumn('clients', 'user_id', "INT NULL");
        $this->addColumn('clients', 'name', "VARCHAR(255) NULL");
        $this->addColumn('clients', 'email', "VARCHAR(255) NULL");
        $this->addColumn('clients', 'phone', "VARCHAR(50) NULL");
        $this->addColumn('clients', 'company_name', "VARCHAR(255) NULL");
        $this->addColumn('clients', 'contact_person', "VARCHAR(255) NULL");
        $this->addColumn('clients', 'address', "TEXT NULL");
        $this->addColumn('clients', 'tax_id', "VARCHAR(100) NULL");
        $this->addColumn('clients', 'registration_number', "VARCHAR(100) NULL");
        $this->addColumn('clients', 'status', "VARCHAR(30) DEFAULT 'active'");
        $this->db->exec("UPDATE clients SET user_id = COALESCE(user_id, id, 1), name = COALESCE(name, company_name, 'Client'), email = COALESCE(email, 'client@itec.local')");
    }

    private function ensureContracts()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS contracts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                client_id INT NOT NULL DEFAULT 1,
                title VARCHAR(255) NOT NULL,
                document_type VARCHAR(120) NULL,
                description TEXT NULL,
                file_path VARCHAR(500) NULL,
                signing_state ENUM('DRAFT','AWAITING_CLIENT','CLIENT_SIGNED','AWAITING_COMPANY','FULLY_SIGNED') DEFAULT 'DRAFT',
                client_signed_at TIMESTAMP NULL,
                company_signed_at TIMESTAMP NULL,
                finalized_at TIMESTAMP NULL,
                created_by INT NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        $this->addColumn('contracts', 'client_id', "INT NOT NULL DEFAULT 1");
        $this->addColumn('contracts', 'client_name', "VARCHAR(255) NULL");
        $this->addColumn('contracts', 'client_email', "VARCHAR(255) NULL");
        $this->addColumn('contracts', 'document_type', "VARCHAR(120) NULL");
        $this->addColumn('contracts', 'description', "TEXT NULL");
        $this->addColumn('contracts', 'file_path', "VARCHAR(500) NULL");
        $this->addColumn('contracts', 'signing_state', "ENUM('DRAFT','AWAITING_CLIENT','CLIENT_SIGNED','AWAITING_COMPANY','FULLY_SIGNED') DEFAULT 'DRAFT'");
        $this->addColumn('contracts', 'client_signed_at', "TIMESTAMP NULL");
        $this->addColumn('contracts', 'company_signed_at', "TIMESTAMP NULL");
        $this->addColumn('contracts', 'finalized_at', "TIMESTAMP NULL");
        $this->addColumn('contracts', 'created_by', "INT NULL DEFAULT 1");
        $this->modifyColumn('contracts', 'client_name', "VARCHAR(255) NULL");
        $this->modifyColumn('contracts', 'client_email', "VARCHAR(255) NULL");
        $this->db->exec("UPDATE contracts SET client_id = COALESCE(client_id, 1), signing_state = COALESCE(signing_state, 'DRAFT')");
    }

    private function ensureVersions()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_versions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                version_no INT NOT NULL,
                saved_by VARCHAR(255) NULL,
                saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                file_path VARCHAR(500) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_contract_version (contract_id, version_no)
            )
        ");

        $this->addColumn('doc_versions', 'contract_id', "INT NULL");
        $this->addColumn('doc_versions', 'version_no', "INT NULL");
        $this->addColumn('doc_versions', 'saved_by', "VARCHAR(255) NULL");
        $this->addColumn('doc_versions', 'saved_at', "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
        $this->addColumn('doc_versions', 'file_path', "VARCHAR(500) NULL");
        $this->modifyColumn('doc_versions', 'saved_by', "VARCHAR(255) NULL");
    }

    private function ensureTrackedChanges()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_tracked_changes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                doc_id INT NULL,
                author_id INT NULL,
                original_text LONGTEXT NULL,
                new_text LONGTEXT NULL,
                status ENUM('pending','accepted','rejected') DEFAULT 'pending',
                reviewed_by INT NULL,
                reviewed_at TIMESTAMP NULL,
                changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        $this->addColumn('doc_tracked_changes', 'contract_id', "INT NULL");
        $this->addColumn('doc_tracked_changes', 'doc_id', "INT NULL");
        $this->addColumn('doc_tracked_changes', 'reviewed_by', "VARCHAR(255) NULL");
        $this->addColumn('doc_tracked_changes', 'reviewed_at', "TIMESTAMP NULL");
        $this->addColumn('doc_tracked_changes', 'updated_at', "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->addColumn('doc_tracked_changes', 'changed_at', "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
        $this->dropForeignKeysForColumn('doc_tracked_changes', 'author_id');
        $this->dropForeignKeysForColumn('doc_tracked_changes', 'reviewed_by');
        $this->modifyColumn('doc_tracked_changes', 'author_id', "VARCHAR(255) NULL");
        $this->modifyColumn('doc_tracked_changes', 'reviewed_by', "VARCHAR(255) NULL");
        $this->db->exec("UPDATE doc_tracked_changes SET contract_id = doc_id WHERE contract_id IS NULL AND doc_id IS NOT NULL");
    }

    private function ensureSignatures()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_signatures (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                doc_id INT NULL,
                signer_id VARCHAR(255) NULL,
                signer_role VARCHAR(50) NULL,
                signature_blob LONGTEXT NULL,
                signature_file_path VARCHAR(500) NULL,
                public_key TEXT NULL,
                doc_hash VARCHAR(255) NOT NULL,
                snapshot_file_path VARCHAR(500) NULL,
                signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->addColumn('doc_signatures', 'contract_id', "INT NULL");
        $this->addColumn('doc_signatures', 'doc_id', "INT NULL");
        $this->addColumn('doc_signatures', 'signer_role', "VARCHAR(50) NULL");
        $this->addColumn('doc_signatures', 'signature_file_path', "VARCHAR(500) NULL");
        $this->addColumn('doc_signatures', 'snapshot_file_path', "VARCHAR(500) NULL");
        $this->addColumn('doc_signatures', 'signature_algorithm', "VARCHAR(50) DEFAULT 'SHA256'");
        $this->modifyColumn('doc_signatures', 'doc_id', "INT NULL");
        $this->dropForeignKeysForColumn('doc_signatures', 'signer_id');
        $this->modifyColumn('doc_signatures', 'signer_id', "VARCHAR(255) NULL");
        $this->modifyColumn('doc_signatures', 'signer_role', "VARCHAR(50) NULL");
        $this->modifyColumn('doc_signatures', 'signature_blob', "LONGTEXT NULL");
        $this->modifyColumn('doc_signatures', 'public_key', "TEXT NULL");
        $this->db->exec("UPDATE doc_signatures SET contract_id = doc_id WHERE contract_id IS NULL AND doc_id IS NOT NULL");
    }

    private function ensureSigningOrder()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_signing_order (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NULL,
                doc_id INT NULL,
                party_role VARCHAR(80) NOT NULL,
                user_id VARCHAR(255) NULL,
                order_no INT DEFAULT 1,
                signing_state VARCHAR(50) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        $this->addColumn('doc_signing_order', 'contract_id', "INT NULL");
        $this->addColumn('doc_signing_order', 'doc_id', "INT NULL");
        $this->dropForeignKeysForColumn('doc_signing_order', 'user_id');
        $this->modifyColumn('doc_signing_order', 'user_id', "VARCHAR(255) NULL");
        $this->db->exec("UPDATE doc_signing_order SET contract_id = doc_id WHERE contract_id IS NULL AND doc_id IS NOT NULL");
    }

    private function ensureAudit()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_signature_audit (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                doc_id INT NULL,
                signer_id VARCHAR(255) NULL,
                ip_address VARCHAR(45) NULL,
                ip VARCHAR(45) NULL,
                user_agent TEXT NULL,
                doc_hash VARCHAR(255) NULL,
                event_type VARCHAR(80) NOT NULL,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->addColumn('doc_signature_audit', 'contract_id', "INT NULL");
        $this->addColumn('doc_signature_audit', 'doc_id', "INT NULL");
        $this->addColumn('doc_signature_audit', 'ip_address', "VARCHAR(45) NULL");
        $this->addColumn('doc_signature_audit', 'ip', "VARCHAR(45) NULL");
        $this->modifyColumn('doc_signature_audit', 'doc_id', "INT NULL");
        $this->dropForeignKeysForColumn('doc_signature_audit', 'signer_id');
        $this->modifyColumn('doc_signature_audit', 'signer_id', "VARCHAR(255) NULL");
        $this->modifyColumn('doc_signature_audit', 'ip_address', "VARCHAR(45) NULL");
        $this->modifyColumn('doc_signature_audit', 'doc_hash', "VARCHAR(255) NULL");
        $this->modifyColumn('doc_signature_audit', 'event_type', "VARCHAR(80) NOT NULL");
        $this->db->exec("UPDATE doc_signature_audit SET contract_id = doc_id WHERE contract_id IS NULL AND doc_id IS NOT NULL");
    }

    private function ensureDistributions()
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS doc_distributions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contract_id INT NOT NULL,
                doc_id INT NULL,
                recipient_email VARCHAR(255) NOT NULL,
                token VARCHAR(128) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                opened_at TIMESTAMP NULL,
                sent_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_token (token)
            )
        ");

        $this->addColumn('doc_distributions', 'contract_id', "INT NULL");
        $this->addColumn('doc_distributions', 'doc_id', "INT NULL");
        $this->addColumn('doc_distributions', 'sent_at', "TIMESTAMP NULL");
        $this->addColumn('doc_distributions', 'status', "VARCHAR(30) DEFAULT 'pending'");
        $this->modifyColumn('doc_distributions', 'doc_id', "INT NULL");
        $this->modifyColumn('doc_distributions', 'token', "VARCHAR(255) NOT NULL");
        $this->db->exec("UPDATE doc_distributions SET contract_id = doc_id WHERE contract_id IS NULL AND doc_id IS NOT NULL");
    }

    private function seedDefaultParties()
    {
        $this->db->exec("
            INSERT INTO users (id, name, email, password, role)
            VALUES (1, 'Demo Staff', 'staff@itec.local', '', 'staff')
            ON DUPLICATE KEY UPDATE name = VALUES(name), role = VALUES(role)
        ");

        $this->db->exec("
            INSERT INTO clients (id, user_id, name, email, company_name, status)
            VALUES (1, 1, 'Demo Client', 'client@itec.local', 'Demo Client Company', 'active')
            ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), name = VALUES(name), email = VALUES(email), company_name = VALUES(company_name), status = VALUES(status)
        ");
    }

    private function addColumn($table, $column, $definition)
    {
        if (!$this->columnExists($table, $column)) {
            $this->db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        }
    }

    private function modifyColumn($table, $column, $definition)
    {
        if ($this->columnExists($table, $column)) {
            try {
                $this->db->exec("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$definition}");
            } catch (\PDOException $error) {
                error_log("Migration skipped {$table}.{$column} modification: " . $error->getMessage());
            }
        }
    }

    private function dropForeignKeysForColumn($table, $column)
    {
        $stmt = $this->db->prepare("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $stmt->execute([$table, $column]);

        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $constraint) {
            $this->db->exec("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }

    private function columnExists($table, $column)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
