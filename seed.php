<?php

require_once __DIR__ . '/vendor/autoload.php';

use Core\Database;

echo "Starting database seeding...\n\n";

$db = Database::getInstance()->getConnection();

// Check if already seeded
$check = $db->query("SELECT COUNT(*) as count FROM contracts")->fetch();
if ($check['count'] > 0) {
    echo "Database already has data. Skipping seed.\n";
    echo "Run TRUNCATE tables first if you want to re-seed.\n";
    exit;
}

try {
    // ============================================
    // Seed Contracts
    // ============================================
    echo "Seeding contracts...\n";
    $db->exec("INSERT INTO contracts (id, client_name, client_email, title, description, file_path, signing_state, created_by) VALUES 
        (1, 'ABC Corporation', 'alice@abccorp.com', 'Service Agreement 2024', 'Annual IT support and maintenance services', '/storage/contracts/contract_1.docx', 'FULLY_SIGNED', 'admin@itec.com'),
        (2, 'XYZ Enterprises', 'bob@xyz.com', 'Financing Contract', 'Equipment financing agreement for 24 months', '/storage/contracts/contract_2.docx', 'AWAITING_COMPANY', 'staff@itec.com'),
        (3, 'Tech Solutions Ltd', 'carol@techsol.com', 'Software License', 'Enterprise software license agreement', '/storage/contracts/contract_3.docx', 'AWAITING_CLIENT', 'admin@itec.com'),
        (4, 'ABC Corporation', 'alice@abccorp.com', 'Consulting Services', 'Business consulting services Q1 2025', '/storage/contracts/contract_4.docx', 'DRAFT', 'staff@itec.com'),
        (5, 'XYZ Enterprises', 'bob@xyz.com', 'Maintenance Contract', 'Preventive maintenance services', '/storage/contracts/contract_5.docx', 'CLIENT_SIGNED', 'admin@itec.com')
    ");

    // ============================================
    // Seed doc_versions (flat storage)
    // ============================================
    echo "Seeding doc_versions...\n";
    $db->exec("INSERT INTO doc_versions (id, contract_id, version_no, saved_by, file_path, saved_at) VALUES 
        (1, 1, 1, 'admin@itec.com', '/storage/contracts/contract_1_v1.docx', '2024-01-10 10:00:00'),
        (2, 1, 2, 'admin@itec.com', '/storage/contracts/contract_1_v2.docx', '2024-01-12 14:30:00'),
        (3, 1, 3, 'admin@itec.com', '/storage/contracts/contract_1_v3.docx', '2024-01-15 09:45:00'),
        (4, 2, 1, 'staff@itec.com', '/storage/contracts/contract_2_v1.docx', '2024-02-01 11:00:00'),
        (5, 3, 1, 'admin@itec.com', '/storage/contracts/contract_3_v1.docx', '2024-02-05 13:20:00')
    ");

    // ============================================
    // Seed doc_tracked_changes
    // ============================================
    echo "Seeding doc_tracked_changes...\n";
    $db->exec("INSERT INTO doc_tracked_changes (id, contract_id, author_id, original_text, new_text, status, changed_at) VALUES 
        (1, 1, 'admin@itec.com', 'Original clause 1', 'Updated clause 1', 'accepted', '2024-01-11 09:00:00'),
        (2, 1, 'admin@itec.com', 'Original clause 2', 'Updated clause 2', 'accepted', '2024-01-13 10:00:00'),
        (3, 2, 'staff@itec.com', 'Draft terms', 'Final terms', 'pending', '2024-02-02 14:00:00'),
        (4, 3, 'admin@itec.com', 'Initial license terms', 'Revised license terms', 'pending', '2024-02-06 09:00:00')
    ");

    // ============================================
    // Seed doc_distributions
    // ============================================
    echo "Seeding doc_distributions...\n";
    $db->exec("INSERT INTO doc_distributions (id, contract_id, recipient_email, token, expires_at, opened_at, status) VALUES 
        (1, 1, 'alice@abccorp.com', 'token_abc123xyz', DATE_ADD(NOW(), INTERVAL 30 DAY), '2024-01-16 10:05:00', 'delivered'),
        (2, 3, 'carol@techsol.com', 'token_def456uvw', DATE_ADD(NOW(), INTERVAL 30 DAY), NULL, 'sent'),
        (3, 2, 'bob@xyz.com', 'token_ghi789rst', DATE_ADD(NOW(), INTERVAL 30 DAY), NULL, 'pending')
    ");

    // ============================================
    // Seed doc_signatures
    // ============================================
    echo "Seeding doc_signatures...\n";
    $db->exec("INSERT INTO doc_signatures (id, contract_id, signer_id, signer_role, signature_blob, public_key, doc_hash, signature_algorithm, signed_at) VALUES 
        (1, 1, 'alice@abccorp.com', 'client', 'U2lnbmF0dXJlIGJsb2IgMQ==', '-----BEGIN PUBLIC KEY-----...', 'a1b2c3d4e5f6', 'SHA256', '2024-01-20 09:00:00'),
        (2, 1, 'admin@itec.com', 'company_rep', 'U2lnbmF0dXJlIGJsb2IgMg==', '-----BEGIN PUBLIC KEY-----...', 'a1b2c3d4e5f6', 'SHA256', '2024-01-21 14:30:00'),
        (3, 3, 'carol@techsol.com', 'client', 'U2lnbmF0dXJlIGJsb2IgMw==', '-----BEGIN PUBLIC KEY-----...', 'b2c3d4e5f6g7', 'SHA256', '2024-02-06 10:15:00'),
        (4, 5, 'bob@xyz.com', 'client', 'U2lnbmF0dXJlIGJsb2IgNA==', '-----BEGIN PUBLIC KEY-----...', 'c3d4e5f6g7h8', 'SHA256', '2024-02-10 11:00:00')
    ");

    // ============================================
    // Seed doc_signing_order
    // ============================================
    echo "Seeding doc_signing_order...\n";
    $db->exec("INSERT INTO doc_signing_order (id, contract_id, party_role, user_id, order_no, signing_state, signed_at) VALUES 
        (1, 1, 'client', 'alice@abccorp.com', 1, 'completed', '2024-01-20 09:00:00'),
        (2, 1, 'company_rep', 'admin@itec.com', 2, 'completed', '2024-01-21 14:30:00'),
        (3, 2, 'client', 'bob@xyz.com', 1, 'pending', NULL),
        (4, 2, 'company_rep', 'staff@itec.com', 2, 'pending', NULL),
        (5, 3, 'client', 'carol@techsol.com', 1, 'pending', NULL),
        (6, 3, 'company_rep', 'admin@itec.com', 2, 'pending', NULL),
        (7, 4, 'client', 'alice@abccorp.com', 1, 'pending', NULL),
        (8, 4, 'company_rep', 'staff@itec.com', 2, 'pending', NULL),
        (9, 5, 'client', 'bob@xyz.com', 1, 'completed', '2024-02-10 11:00:00'),
        (10, 5, 'company_rep', 'admin@itec.com', 2, 'pending', NULL)
    ");

    // ============================================
    // Seed doc_signature_audit
    // ============================================
    echo "Seeding doc_signature_audit...\n";
    $db->exec("INSERT INTO doc_signature_audit (id, contract_id, signer_id, ip_address, user_agent, doc_hash, event_type, timestamp) VALUES 
        (1, 1, 'alice@abccorp.com', '192.168.1.100', 'Mozilla/5.0', 'a1b2c3d4e5f6', 'signature_created', '2024-01-20 09:00:00'),
        (2, 1, 'admin@itec.com', '192.168.1.10', 'Mozilla/5.0', 'a1b2c3d4e5f6', 'signature_created', '2024-01-21 14:30:00'),
        (3, 1, 'admin@itec.com', '192.168.1.10', 'Mozilla/5.0', 'a1b2c3d4e5f6', 'seal_applied', '2024-01-21 14:35:00'),
        (4, 1, 'system', '192.168.1.10', 'System', 'a1b2c3d4e5f6', 'signature_verified', '2024-01-21 14:36:00'),
        (5, 3, 'carol@techsol.com', '10.0.0.50', 'Chrome/120', 'b2c3d4e5f6g7', 'signature_created', '2024-02-06 10:15:00'),
        (6, 5, 'bob@xyz.com', '192.168.1.200', 'Safari/17.0', 'c3d4e5f6g7h8', 'signature_created', '2024-02-10 11:00:00')
    ");

    // ============================================
    // Create storage directories and dummy files
    // ============================================
    echo "\nCreating storage directories and dummy files...\n";
    
    $storageDir = __DIR__ . '/storage/contracts';
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0777, true);
    }
    
    // Create dummy contract files
    for ($i = 1; $i <= 5; $i++) {
        $filePath = $storageDir . "/contract_{$i}.docx";
        if (!file_exists($filePath)) {
            file_put_contents($filePath, "This is test contract {$i} content.\n\nContract terms and conditions go here.");
            echo "  Created: {$filePath}\n";
        }
    }
    
    // Create version files
    $versionFiles = [
        'contract_1_v1.docx', 'contract_1_v2.docx', 'contract_1_v3.docx',
        'contract_2_v1.docx', 'contract_3_v1.docx'
    ];
    
    foreach ($versionFiles as $file) {
        $filePath = $storageDir . "/{$file}";
        if (!file_exists($filePath)) {
            file_put_contents($filePath, "Version file: {$file}");
            echo "  Created: {$filePath}\n";
        }
    }

    // ============================================
    // Success Message
    // ============================================
    echo "\n=========================================\n";
    echo "Seeding completed successfully!\n";
    echo "=========================================\n";
    echo "\nSeeded data:\n";
    echo "  - contracts: 5 records\n";
    echo "  - doc_versions: 5 records\n";
    echo "  - doc_tracked_changes: 4 records\n";
    echo "  - doc_distributions: 3 records\n";
    echo "  - doc_signatures: 4 records\n";
    echo "  - doc_signing_order: 10 records\n";
    echo "  - doc_signature_audit: 6 records\n";

    echo "\n=========================================\n";
    echo "Verification:\n";
    echo "=========================================\n";
    
    $tables = ['contracts', 'doc_versions', 'doc_tracked_changes', 'doc_distributions', 'doc_signatures', 'doc_signing_order', 'doc_signature_audit'];
    foreach ($tables as $table) {
        $count = $db->query("SELECT COUNT(*) as count FROM {$table}")->fetch();
        echo "  - {$table}: {$count['count']} records\n";
    }

} catch (PDOException $e) {
    echo "\n=========================================\n";
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "=========================================\n";
    echo "Make sure you have run migrations first at /migrate\n";
    echo "\nRun migrations in this order:\n";
    echo "  1. Visit: /migrate\n";
    echo "  2. Then visit: /seed\n";
}