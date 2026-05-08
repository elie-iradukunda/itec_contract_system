<?php

require_once __DIR__ . '/vendor/autoload.php';

use Core\Database;

echo "Starting database seeding...\n\n";

$db = Database::getInstance()->getConnection();

// Check if already seeded
$check = $db->query("SELECT COUNT(*) as count FROM users")->fetch();
if ($check['count'] > 0) {
    echo "Database already has data. Skipping seed.\n";
    echo "Run TRUNCATE tables first if you want to re-seed 3.\n";
    exit;
}

try {
    // ============================================
    // Seed Users (Parent Table)
    // ============================================
    echo "Seeding users...\n";
    $db->exec("INSERT IGNORE INTO users (id, name, email, password, role) VALUES 
        (1, 'Admin User', 'admin@itec.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'admin'),
        (2, 'John Staff', 'staff@itec.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'staff'),
        (3, 'Alice Client', 'alice@client.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'client'),
        (4, 'Bob Client', 'bob@client.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'client'),
        (5, 'Carol Client', 'carol@client.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'client'),
        (6, 'David Staff', 'david@itec.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'staff')
    ");

    // ============================================
    // Seed Clients (Depends on Users)
    // ============================================
    echo "Seeding clients...\n";
    $db->exec("INSERT IGNORE INTO clients (id, name, email, phone, company_name) VALUES 
        (1, 'Alice Johnson', 'alice@abccorp.com', '+1234567890', 'ABC Corporation'),
        (2, 'Bob Smith', 'bob@xyz.com', '+1234567891', 'XYZ Enterprises'),
        (3, 'Carol Williams', 'carol@techsol.com', '+1234567892', 'Tech Solutions Ltd'),
        (4, 'David Brown', 'david@services.com', '+1234567893', 'David Brown Consulting'),
        (5, 'Emma Davis', 'emma@partners.com', '+1234567894', 'Emma Davis Partners')
    ");

    // ============================================
    // Seed Contracts (Depends on Clients)
    // ============================================
    echo "Seeding contracts...\n";
    $db->exec("INSERT IGNORE INTO contracts (id, client_id, title, description, file_path, signing_state, created_by) VALUES 
        (1, 1, 'Service Agreement 2024', 'Annual IT support and maintenance services', '/storage/contracts/1/contract.docx', 'FULLY_SIGNED', 1),
        (2, 2, 'Financing Contract', 'Equipment financing agreement for 24 months', '/storage/contracts/2/contract.docx', 'AWAITING_COMPANY', 2),
        (3, 3, 'Software License', 'Enterprise software license agreement', '/storage/contracts/3/contract.docx', 'AWAITING_CLIENT', 1),
        (4, 1, 'Consulting Services', 'Business consulting services Q1 2025', '/storage/contracts/4/contract.docx', 'DRAFT', 2),
        (5, 2, 'Maintenance Contract', 'Preventive maintenance services', '/storage/contracts/5/contract.docx', 'CLIENT_SIGNED', 1)
    ");

    // ============================================
    // Seed Contract Versions (Depends on Contracts)
    // ============================================
    echo "Seeding contract versions...\n";
    $db->exec("INSERT IGNORE INTO contract_versions (id, contract_id, version_no, saved_by, file_path, saved_at) VALUES 
        (1, 1, 1, 1, '/storage/contracts/1/v1.docx', '2024-01-10 10:00:00'),
        (2, 1, 2, 1, '/storage/contracts/1/v2.docx', '2024-01-12 14:30:00'),
        (3, 1, 3, 1, '/storage/contracts/1/v3.docx', '2024-01-15 09:45:00'),
        (4, 2, 1, 2, '/storage/contracts/2/v1.docx', '2024-02-01 11:00:00'),
        (5, 3, 1, 1, '/storage/contracts/3/v1.docx', '2024-02-05 13:20:00')
    ");

    // ============================================
    // Seed Contract Signatures (Depends on Contracts & Users)
    // ============================================
    echo "Seeding contract signatures...\n";
    $db->exec("INSERT IGNORE INTO contract_signatures (id, contract_id, signer_id, signer_role, signature_file_path, public_key, document_hash, ip_address, user_agent, signed_at) VALUES 
        (1, 1, 3, 'client', '/storage/signatures/sig_1_3_12345.sig', '-----BEGIN PUBLIC KEY-----', 'a1b2c3d4e5f6', '192.168.1.100', 'Mozilla/5.0', '2024-01-20 09:00:00'),
        (2, 1, 1, 'company_rep', '/storage/signatures/sig_1_1_12346.sig', '-----BEGIN PUBLIC KEY-----', 'a1b2c3d4e5f6', '192.168.1.10', 'Mozilla/5.0', '2024-01-21 14:30:00'),
        (3, 3, 5, 'client', '/storage/signatures/sig_3_5_12347.sig', '-----BEGIN PUBLIC KEY-----', 'b2c3d4e5f6g7', '10.0.0.50', 'Chrome/120', '2024-02-06 10:15:00')
    ");

    // ============================================
    // Seed Contract Seals (Depends on Contracts & Users)
    // ============================================
    echo "Seeding contract seals...\n";
    $db->exec("INSERT IGNORE INTO contract_seals (id, contract_id, applied_by, seal_image_path, approval_code, applied_at) VALUES 
        (1, 1, 1, '/storage/seals/company_seal.png', 'ULID_ABC123XYZ456', '2024-01-21 14:35:00'),
        (2, 3, 1, '/storage/seals/company_seal.png', 'ULID_DEF456UVW789', '2024-02-06 10:20:00')
    ");

    // ============================================
    // Seed Audit Logs (Depends on Contracts & Users)
    // Note: user_id 0 is allowed for system actions
    // ============================================
    echo "Seeding audit logs...\n";
    
    // Get existing user IDs to avoid foreign key errors
    $adminId = 1;
    $clientId = 3;
    
    try {
        $adminCheck = $db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
        if ($adminCheck) $adminId = $adminCheck['id'];
        
        $clientCheck = $db->query("SELECT id FROM users WHERE role = 'client' LIMIT 1")->fetch();
        if ($clientCheck) $clientId = $clientCheck['id'];
    } catch (Exception $e) {
        // Use defaults if query fails
    }
    
    $db->exec("INSERT IGNORE INTO audit_logs (id, contract_id, user_id, action, event_type, ip_address, user_agent, details, created_at) VALUES 
        (1, 1, {$adminId}, 'Contract created', 'create', '192.168.1.10', 'Mozilla/5.0', '{\"title\":\"Service Agreement\"}', '2024-01-10 09:00:00'),
        (2, 1, {$adminId}, 'Contract edited', 'edit', '192.168.1.10', 'Mozilla/5.0', '{\"changes\":\"Updated terms\"}', '2024-01-12 14:30:00'),
        (3, 1, {$clientId}, 'Contract signed by client', 'sign', '192.168.1.100', 'Chrome/120', '{\"role\":\"client\"}', '2024-01-20 09:00:00'),
        (4, 1, {$adminId}, 'Contract sealed by company', 'seal', '192.168.1.10', 'Mozilla/5.0', '{\"approval_code\":\"ULID_ABC123XYZ456\"}', '2024-01-21 14:35:00'),
        (5, 1, 0, 'State changed', 'state_change', '192.168.1.10', 'System', '{\"from\":\"DRAFT\",\"to\":\"AWAITING_CLIENT\"}', '2024-01-15 10:00:00'),
        (6, 2, {$adminId}, 'Contract submitted for signing', 'state_change', '192.168.1.10', 'Mozilla/5.0', '{\"from\":\"DRAFT\",\"to\":\"AWAITING_CLIENT\"}', '2024-02-01 11:00:00'),
        (7, 3, {$clientId}, 'Contract signed', 'sign', '10.0.0.50', 'Chrome/120', '{\"role\":\"client\"}', '2024-02-06 10:15:00')
    ");

    // ============================================
    // Seed Notifications (Depends on Contracts)
    // ============================================
    echo "Seeding notifications...\n";
    $db->exec("INSERT IGNORE INTO notifications (id, contract_id, recipient_email, token, sent_at, expires_at, status) VALUES 
        (1, 1, 'alice@client.com', 'token_abc123xyz', '2024-01-15 10:05:00', DATE_ADD('2024-01-15 10:05:00', INTERVAL 30 DAY), 'delivered'),
        (2, 3, 'carol@client.com', 'token_def456uvw', '2024-02-05 14:00:00', DATE_ADD('2024-02-05 14:00:00', INTERVAL 30 DAY), 'sent'),
        (3, 2, 'bob@client.com', 'token_ghi789rst', '2024-02-01 11:05:00', DATE_ADD('2024-02-01 11:05:00', INTERVAL 30 DAY), 'pending')
    ");

    // ============================================
    // Success Message
    // ============================================
    echo "\n=========================================\n";
    echo "Seeding completed successfully!\n";
    echo "=========================================\n";
    echo "\nSeeded data:\n";
    echo "  - Users: 6 records\n";
    echo "  - Clients: 5 records\n";
    echo "  - Contracts: 5 records\n";
    echo "  - Contract Versions: 5 records\n";
    echo "  - Contract Signatures: 3 records\n";
    echo "  - Contract Seals: 2 records\n";
    echo "  - Audit Logs: 7 records\n";
    echo "  - Notifications: 3 records\n";

} catch (PDOException $e) {
    echo "\n=========================================\n";
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "=========================================\n";
    echo "Make sure you have run migrations first at /migrate\n";
    echo "\nRun migrations in this order:\n";
    echo "  1. Visit: /migrate\n";
    echo "  2. Then visit: /seed\n";
}