<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Services\OscarStateMachineService;
use Core\Database;

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';
$currentState = null;

// Get existing contract
$stmt = $db->query("SELECT id, signing_state FROM contracts LIMIT 1");
$contract = $stmt->fetch();

if (!$contract) {
    die("No contract found. Create a contract first.");
}

$contractId = $contract['id'];
$stateMachine = new OscarStateMachineService($contractId);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'submit':
                $result = $stateMachine->submitForSigning('admin@itec.com');
                $message = $result['message'];
                break;
                
            case 'client_sign':
                $result = $stateMachine->clientSign('client@email.com', 'hash_' . time());
                $message = $result['message'];
                break;
                
            case 'escalate':
                $result = $stateMachine->escalateToCompany('admin@itec.com');
                $message = $result['message'];
                break;
                
            case 'company_sign':
                $result = $stateMachine->companySign('admin@itec.com', 'final_hash_' . time());
                $message = $result['message'];
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get current state
$stateInfo = $stateMachine->getCurrentState();
$currentState = $stateInfo['state'];
$allowedActions = $stateInfo['allowed_actions'];
$timeline = $stateInfo['timeline'] ?? [];
print_r($currentState);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Task O3 - Sequential Signing State Machine</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .status { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #28a745; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #dc3545; color: #721c24; }
        .info { background: #e2f3fc; border: 1px solid #17a2b8; color: #0c5460; }
        .step { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
        .step h3 { margin-top: 0; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 4px; }
        button:hover { background: #0056b3; }
        .flow { display: flex; justify-content: space-between; margin: 20px 0; flex-wrap: wrap; }
        .flow-item { flex: 1; text-align: center; padding: 10px; margin: 5px; background: #f0f0f0; border-radius: 5px; }
        .flow-item.active { background: #007bff; color: white; }
        .flow-item.completed { background: #28a745; color: white; }
        .timeline { margin-top: 15px; padding: 10px; background: #e9ecef; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Task O3: Sequential Signing State Machine</h1>
        <p>Atomic Transactions | Auto-Email | Stamp Pipeline on FULLY_SIGNED</p>

        <!-- Flow Visualization -->
        <div class="flow">
            <div class="flow-item <?php echo $currentState == 'DRAFT' ? 'active' : ($currentState != 'DRAFT' ? 'completed' : ''); ?>">
                DRAFT
            </div>
            <div class="flow-item <?php echo $currentState == 'AWAITING_CLIENT' ? 'active' : ($currentState == 'CLIENT_SIGNED' || $currentState == 'AWAITING_COMPANY' || $currentState == 'FULLY_SIGNED' ? 'completed' : ''); ?>">
                AWAITING CLIENT
            </div>
            <div class="flow-item <?php echo $currentState == 'CLIENT_SIGNED' ? 'active' : ($currentState == 'AWAITING_COMPANY' || $currentState == 'FULLY_SIGNED' ? 'completed' : ''); ?>">
                CLIENT SIGNED
            </div>
            <div class="flow-item <?php echo $currentState == 'AWAITING_COMPANY' ? 'active' : ($currentState == 'FULLY_SIGNED' ? 'completed' : ''); ?>">
                AWAITING COMPANY
            </div>
            <div class="flow-item <?php echo $currentState == 'FULLY_SIGNED' ? 'active completed' : ''; ?>">
                FULLY SIGNED
            </div>
        </div>

        <?php if ($message): ?>
        <div class="status success">SUCCESS: <?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="status error">ERROR: <?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Contract Details -->
        <div class="step">
            <h2>Contract Details</h2>
            <p><strong>Contract ID:</strong> <?php echo $contractId; ?></p>
            <p><strong>Current State:</strong> <span style="font-weight: bold; color: #007bff;"><?php echo $currentState; ?></span></p>
            <p><strong>Allowed Actions:</strong> <?php echo is_array($allowedActions) ? implode(', ', $allowedActions) : 'None'; ?></p>
            
            <?php if (!empty($timeline) && (isset($timeline['client_signed_at']) || isset($timeline['company_signed_at']) || isset($timeline['finalized_at']))): ?>
            <div class="timeline">
                <strong>Timeline:</strong><br>
                <?php if (isset($timeline['client_signed_at']) && $timeline['client_signed_at']): ?>
                ✓ Client Signed: <?php echo $timeline['client_signed_at']; ?><br>
                <?php endif; ?>
                <?php if (isset($timeline['company_signed_at']) && $timeline['company_signed_at']): ?>
                ✓ Company Signed: <?php echo $timeline['company_signed_at']; ?><br>
                <?php endif; ?>
                <?php if (isset($timeline['finalized_at']) && $timeline['finalized_at']): ?>
                ✓ Finalized: <?php echo $timeline['finalized_at']; ?><br>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="step">
            <h2>Execute Actions</h2>
            
            <?php if (is_array($allowedActions) && in_array('submit_for_signing', $allowedActions)): ?>
            <form method="POST" style="margin-bottom: 20px;">
                <input type="hidden" name="action" value="submit">
                <h3>Step 1: Submit for Signing</h3>
                <p>Action: DRAFT → AWAITING_CLIENT</p>
                <p><small>Email will be sent to client.</small></p>
                <button type="submit">Submit to Client</button>
            </form>
            <?php endif; ?>

            <?php if (is_array($allowedActions) && in_array('client_sign', $allowedActions)): ?>
            <form method="POST" style="margin-bottom: 20px;">
                <input type="hidden" name="action" value="client_sign">
                <h3>Step 2: Client Signs</h3>
                <p>Action: AWAITING_CLIENT → CLIENT_SIGNED</p>
                <p><small>Email will be sent to company rep.</small></p>
                <button type="submit">Client Sign</button>
            </form>
            <?php endif; ?>

            <?php if (is_array($allowedActions) && in_array('escalate_to_company', $allowedActions)): ?>
            <form method="POST" style="margin-bottom: 20px;">
                <input type="hidden" name="action" value="escalate">
                <h3>Step 3: Escalate to Company</h3>
                <p>Action: CLIENT_SIGNED → AWAITING_COMPANY</p>
                <p><small>Email reminder sent to company rep.</small></p>
                <button type="submit">Escalate</button>
            </form>
            <?php endif; ?>

            <?php if (is_array($allowedActions) && in_array('company_sign', $allowedActions)): ?>
            <form method="POST" style="margin-bottom: 20px;">
                <input type="hidden" name="action" value="company_sign">
                <h3>Step 4: Company Signs + Seal</h3>
                <p>Action: AWAITING_COMPANY → FULLY_SIGNED</p>
                <p><small>Triggers stamp pipeline + final email to client.</small></p>
                <button type="submit">Company Sign & Seal</button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Info Box -->
        <div class="status info">
            <strong>How State Machine Works:</strong><br>
            • Each transition is wrapped in a database transaction<br>
            • Audit log written on every state change<br>
            • Auto-email sent to next party after each transition<br>
            • Stamp pipeline triggered when FULLY_SIGNED state reached<br>
            • Contract body is FROZEN after first signature
        </div>
    </div>
</body>
</html>