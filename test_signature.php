<?php

require_once __DIR__ . '/vendor/autoload.php';

use Services\OscarSignatureService;
use Core\Database;

// Create storage directories if not exist
$baseDir = __DIR__;
$contractsDir = $baseDir . '/storage/contracts/1/';
$signaturesDir = $baseDir . '/storage/signatures/';

if (!is_dir($contractsDir)) {
    mkdir($contractsDir, 0777, true);
}
if (!is_dir($signaturesDir)) {
    mkdir($signaturesDir, 0777, true);
}

$db = Database::getInstance()->getConnection();
$signatureService = new OscarSignatureService();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $contractId = $_POST['contract_id'] ?? 1;
        
        switch ($_POST['action']) {
            case 'create_contract':
                // Create test contract file
                $testFilePath = $contractsDir . 'contract.docx';
                $testContent = "This is a test contract document for signature testing.\n";
                $testContent .= "Contract Terms:\n";
                $testContent .= "1. This is a test agreement\n";
                $testContent .= "2. Valid for testing purposes only\n";
                $testContent .= "3. Date: " . date('Y-m-d H:i:s') . "\n";
                file_put_contents($testFilePath, $testContent);
                
                // Clear and insert contract
                $db->exec("DELETE FROM contracts WHERE id = 1");
                $db->exec("DELETE FROM doc_signatures WHERE contract_id = 1");
                
                $sql = "INSERT INTO contracts (id, title, description, file_path, signing_state, created_by) VALUES 
                        (1, 'Test Contract', 'This is a test contract for signature verification', :file_path, 'DRAFT', 'test@user.com')";
                $stmt = $db->prepare($sql);
                $stmt->execute(['file_path' => $testFilePath]);
                
                $message = "Test contract created successfully!";
                break;
                
            case 'sign':
                $contractId = $_POST['contract_id'];
                $signerId = $_POST['signer_id'];
                
                $stmt = $db->prepare("SELECT file_path FROM contracts WHERE id = ?");
                $stmt->execute([$contractId]);
                $contract = $stmt->fetch();
                
                if ($contract) {
                    $result = $signatureService->signDocument($contractId, $signerId, $contract['file_path']);
                    if ($result['success']) {
                        $message = "Document signed successfully! Signature ID: " . $result['signature_id'];
                    } else {
                        $error = "Signing failed: " . $result['error'];
                    }
                } else {
                    $error = "Contract not found";
                }
                break;
                
            case 'verify':
                $contractId = $_POST['contract_id'];
                $result = $signatureService->verifyDocument($contractId);
                if ($result['valid']) {
                    $message = " Document is VALID and has not been tampered!";
                } else {
                    $error = " " . ($result['warning'] ?? 'Document verification failed');
                }
                break;
                
            case 'tamper':
                $contractId = $_POST['contract_id'];
                $stmt = $db->prepare("SELECT file_path FROM contracts WHERE id = ?");
                $stmt->execute([$contractId]);
                $contract = $stmt->fetch();
                if ($contract && file_exists($contract['file_path'])) {
                    $content = file_get_contents($contract['file_path']);
                    file_put_contents($contract['file_path'], "TAMPERED: " . $content);
                    $message = "Contract file has been tampered! Run verification again to detect.";
                }
                break;
        }
    }
}

// Get current contract data
$contract = null;
$stmt = $db->query("SELECT id, title, file_path, signing_state, created_by, created_at FROM contracts WHERE id = 1");
$contract = $stmt->fetch();

$signatures = [];
if ($contract) {
    $stmt = $db->prepare("SELECT * FROM doc_signatures WHERE contract_id = ? ORDER BY signed_at DESC");
    $stmt->execute([$contract['id']]);
    $signatures = $stmt->fetchAll();
}

// Verification result
$verification = null;
if ($contract) {
    $verification = $signatureService->verifyDocument($contract['id']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oscar Task O1 - Digital Signature Engine</title>
      <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        .subtitle {
            text-align: center;
            color: rgba(255,255,255,0.9);
            margin-bottom: 30px;
            font-size: 1.1em;
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
        .status.warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
        }
        .button-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        button, .button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        button:hover, .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        button.danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        button.success {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
        }
        .input-group {
            margin-bottom: 15px;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .input-group input, .input-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: #f5f5f5;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge.success {
            background: #d4edda;
            color: #155724;
        }
        .badge.danger {
            background: #f8d7da;
            color: #721c24;
        }
        .badge.warning {
            background: #fff3cd;
            color: #856404;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
        }
        .hash {
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
            background: #f5f5f5;
            padding: 10px;
            border-radius: 6px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        @media (max-width: 768px) {
            .button-group {
                flex-direction: column;
            }
            button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Task O1: Digital Signature Engine</h1>
        <div class="subtitle">SHA-256 Hashing | OpenSSL Signatures | Tamper Detection | Signer Chain</div>

        <?php if ($message): ?>
        <div class="status success"> <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="status error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Contract Status Card -->
        <div class="card">
            <h2>Contract Details</h2>
            <?php if ($contract): ?>
            <div class="info-box">
                <strong>ID:</strong> <?php echo $contract['id']; ?><br>
                <strong>Title:</strong> <?php echo htmlspecialchars($contract['title']); ?><br>
                <strong>State:</strong> <span class="badge <?php echo $contract['signing_state'] == 'DRAFT' ? 'warning' : 'success'; ?>"><?php echo $contract['signing_state']; ?></span><br>
                <strong>File Path:</strong> <code><?php echo htmlspecialchars($contract['file_path']); ?></code><br>
                <strong>Created:</strong> <?php echo $contract['created_at']; ?><br>
                <strong>File Exists:</strong> <span class="badge <?php echo file_exists($contract['file_path']) ? 'success' : 'danger'; ?>">
                    <?php echo file_exists($contract['file_path']) ? 'YES' : 'NO'; ?>
                </span>
            </div>
            <?php else: ?>
            <div class="status warning">No contract found. Create one below.</div>
            <?php endif; ?>
        </div>

        <div class="grid-2">
            <!-- Actions Card -->
            <div class="card">
                <h2>Actions</h2>
                <form method="POST">
                    <input type="hidden" name="contract_id" value="1">
                    
                    <div class="button-group">
                        <button type="submit" name="action" value="create_contract" class="success">Create Test Contract</button>
                    </div>
                    
                    <div class="input-group">
                        <label>Signer ID (Email or Username)</label>
                        <input type="text" name="signer_id" value="test@user.com" required>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" name="action" value="sign">Sign Document</button>
                        <button type="submit" name="action" value="verify">Verify Document</button>
                        <button type="submit" name="action" value="tamper" class="danger">Simulate Tamper</button>
                    </div>
                </form>
            </div>

            <!-- Verification Result Card -->
            <?php if ($verification): ?>
            <div class="card">
                <h2>Verification Result</h2>
                <div class="status <?php echo $verification['valid'] ? 'success' : 'error'; ?>">
                    <strong>Status:</strong> <?php echo $verification['valid'] ? 'VALID ✓' : 'INVALID ✗'; ?><br>
                    <?php if (isset($verification['warning'])): ?>
                    <strong>Warning:</strong> <?php echo htmlspecialchars($verification['warning']); ?><br>
                    <?php endif; ?>
                    <?php if (isset($verification['tampered'])): ?>
                    <strong>Tampered:</strong> <?php echo $verification['tampered'] ? 'YES' : 'NO'; ?><br>
                    <?php endif; ?>
                    <?php if (isset($verification['signed_at'])): ?>
                    <strong>Signed At:</strong> <?php echo $verification['signed_at']; ?><br>
                    <?php endif; ?>
                    <?php if (isset($verification['message'])): ?>
                    <strong>Message:</strong> <?php echo htmlspecialchars($verification['message']); ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Signatures Card -->
        <div class="card">
            <h2>Signature Chain (doc_signatures table)</h2>
            <?php if (count($signatures) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Signer ID</th>
                        <th>Document Hash (SHA-256)</th>
                        <th>Signed At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($signatures as $sig): ?>
                    <tr>
                        <td><?php echo $sig['id']; ?></td>
                        <td><?php echo htmlspecialchars($sig['signer_id']); ?></td>
                        <td><code class="hash"><?php echo substr($sig['doc_hash'], 0, 20) . '...'; ?></code></td>
                        <td><?php echo $sig['signed_at']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="status warning">No signatures found. Sign the document first.</div>
            <?php endif; ?>
        </div>

        <!-- Hash Info Card -->
        <?php if ($contract && file_exists($contract['file_path'])): ?>
        <div class="card">
            <h2>Current Document Hash</h2>
            <div class="hash">
                <strong>SHA-256:</strong> <?php echo hash_file('sha256', $contract['file_path']); ?>
            </div>
            <div style="margin-top: 10px;">
                <small>File size: <?php echo filesize($contract['file_path']); ?> bytes</small>
            </div>
        </div>
        <?php endif; ?>

        <!-- Key Storage Info -->
        <div class="card">
            <h2>Storage Locations</h2>
            <div class="info-box">
                <strong>Private Key:</strong> <code><?php echo __DIR__ . '/storage/keys/private.key'; ?></code><br>
                <strong>Public Key:</strong> <code><?php echo __DIR__ . '/storage/keys/public.key'; ?></code><br>
                <strong>Signatures:</strong> <code><?php echo __DIR__ . '/storage/signatures/'; ?></code><br>
                <strong>Contract Files:</strong> <code><?php echo __DIR__ . '/storage/contracts/'; ?></code>
            </div>
        </div>
    </div>
</body>
</html>