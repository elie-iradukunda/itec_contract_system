<?php

require_once __DIR__ . '/vendor/autoload.php';

use Services\OscarSealService;
use Core\Database;

$db = Database::getInstance()->getConnection();
$sealService = new OscarSealService();

$message = '';
$error = '';

// Create contract if none exists
$stmt = $db->query("SELECT COUNT(*) as count FROM contracts");
$count = $stmt->fetch();

if ($count['count'] == 0) {
    $contractsDir = __DIR__ . '/storage/contracts/1/';
    if (!is_dir($contractsDir)) {
        mkdir($contractsDir, 0777, true);
    }
    
    $testFilePath = $contractsDir . 'contract.docx';
    file_put_contents($testFilePath, "This is a test contract for seal testing.\n\nTerms:\n1. Test agreement\n2. Valid for testing\nDate: " . date('Y-m-d'));
    
    $db->exec("INSERT INTO contracts (id, title, description, file_path, signing_state, created_by) VALUES 
        (1, 'Test Contract', 'Test for seal', '{$testFilePath}', 'AWAITING_COMPANY', 'admin@test.com')");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'apply_seal') {
        $contractId = $_POST['contract_id'];
        $approverName = $_POST['approver_name'];
        
        $result = $sealService->applySeal($contractId, $approverName);
        
        if ($result['success']) {
            $message = "Seal applied! Approval Code: " . $result['approval_code'];
        } else {
            $error = $result['error'];
        }
    }
}

// Get contract info
$contract = null;
$stmt = $db->query("SELECT * FROM contracts WHERE id = 1");
$contract = $stmt->fetch();

$sealInfo = $sealService->getSealInfo(1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Task O2 - Company Seal</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .status.success {
            background: #d4edda;
            border: 1px solid #28a745;
            color: #155724;
        }
        .status.error {
            background: #f8d7da;
            border: 1px solid #dc3545;
            color: #721c24;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        input, select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            width: 100%;
            margin-bottom: 15px;
        }
        label {
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="color: white; text-align: center;">Task O2: Company Seal + Stamp Pipeline</h1>
        <p style="color: white; text-align: center; margin-bottom: 30px;">Apply company seal and flattened approval stamp to contracts</p>

        <?php if ($message): ?>
        <div class="status success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="status error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Contract Info -->
        <div class="card">
            <h2>Current Contract</h2>
            <?php if ($contract): ?>
            <div class="info-box">
                <strong>ID:</strong> <?php echo $contract['id']; ?><br>
                <strong>Title:</strong> <?php echo htmlspecialchars($contract['title']); ?><br>
                <strong>State:</strong> <?php echo $contract['signing_state']; ?><br>
                <strong>File:</strong> <code><?php echo $contract['file_path']; ?></code><br>
                <strong>File exists:</strong> <?php echo file_exists($contract['file_path']) ? 'Yes' : 'No'; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Apply Seal Form -->
        <div class="card">
            <h2>Apply Company Seal</h2>
            <form method="POST">
                <input type="hidden" name="contract_id" value="1">
                <input type="hidden" name="action" value="apply_seal">
                
                <label>Approver Name</label>
                <input type="text" name="approver_name" value="John Doe, Authorized Signatory" required>
                
                <button type="submit">Apply Seal & Stamp</button>
            </form>
        </div>

        <!-- Seal Info -->
        <?php if ($sealInfo): ?>
        <div class="card">
            <h2>Applied Seal Information</h2>
            <div class="info-box">
                <strong>Approval Code:</strong> <code><?php echo $sealInfo['approval_code']; ?></code><br>
                <strong>Applied By:</strong> <?php echo $sealInfo['applied_by']; ?><br>
                <strong>Applied At:</strong> <?php echo $sealInfo['applied_at']; ?><br>
                <strong>Seal Image:</strong> <code><?php echo $sealInfo['seal_image_path']; ?></code>
            </div>
        </div>
        <?php endif; ?>

        <!-- Storage Locations -->
        <div class="card">
            <h2>Storage</h2>
            <div class="info-box">
                <strong>Seal Image:</strong> <code><?php echo __DIR__ . '/storage/seals/company_seal.png'; ?></code><br>
                <strong>Contract Files:</strong> <code><?php echo __DIR__ . '/storage/contracts/'; ?></code>
            </div>
        </div>
    </div>
</body>
</html>