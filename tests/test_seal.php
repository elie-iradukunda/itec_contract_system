<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Services\OscarSealService;
use Core\Database;

$db = Database::getInstance()->getConnection();
$sealService = new OscarSealService();

$message = '';
$error = '';
$result = null;

// Get existing contract
$stmt = $db->query("SELECT id, file_path FROM contracts LIMIT 1");
$contract = $stmt->fetch();

if (!$contract) {
    die("No contract found. Run test_signature.php first to create a contract.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_seal') {
    $contractId = $contract['id'];
    $approverName = $_POST['approver_name'];
    
    $result = $sealService->applySeal($contractId, $approverName);
    
    if ($result['success']) {
        $message = "Seal applied successfully. Approval Code: " . $result['approval_code'];
    } else {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Task O2 - Company Seal Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { border: 1px solid #ccc; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #28a745; color: #155724; padding: 10px; margin-bottom: 15px; }
        .error { background: #f8d7da; border: 1px solid #dc3545; color: #721c24; padding: 10px; margin-bottom: 15px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        input[type="text"] { width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Task O2: Company Seal + Auto-Stamp Pipeline</h1>

        <?php if ($message): ?>
        <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Contract Details</h2>
            <p><strong>ID:</strong> <?php echo $contract['id']; ?></p>
            <p><strong>File Path:</strong> <code><?php echo $contract['file_path']; ?></code></p>
            <p><strong>File Exists:</strong> <?php echo file_exists($contract['file_path']) ? 'Yes' : 'No'; ?></p>
        </div>

        <div class="card">
            <h2>Apply Company Seal</h2>
            <form method="POST">
                <input type="hidden" name="action" value="apply_seal">
                <label>Authorized Signatory Name</label>
                <input type="text" name="approver_name" value="John Doe, Authorized Signatory" required>
                <button type="submit">Apply Seal</button>
            </form>
        </div>

        <?php if ($result && $result['success']): ?>
        <div class="card">
            <h2>Sealing Result</h2>
            <p><strong>Approval Code:</strong> <code><?php echo $result['approval_code']; ?></code></p>
            <p><strong>Sealed File:</strong> <code><?php echo $result['sealed_file']; ?></code></p>
            <p><strong>Original File Preserved:</strong> <code><?php echo $result['original_file']; ?></code></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>