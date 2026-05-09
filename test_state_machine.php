<?php

require_once __DIR__ . '/vendor/autoload.php';

use Core\Database;
use Services\OscarStateMachineService;

echo "<pre>";
echo "=========================================\n";
echo "Testing Oscar State Machine\n";
echo "=========================================\n\n";


$db = Database::getInstance()->getConnection();
$stateMachine = new OscarStateMachineService();

// Get a contract to test
$stmt = $db->query("SELECT id, title, signing_state FROM contracts LIMIT 1");
$contract = $stmt->fetch();

if (!$contract) {
    echo "No contracts found. Run /seed first.\n";
    exit;
}

$contractId = $contract['id'];
$currentState = $contract['signing_state'];

echo "Testing with Contract ID: {$contractId}\n";
echo "Title: {$contract['title']}\n";
echo "Current State: {$currentState}\n\n";

echo "-----------------------------------------\n";
echo "Test 1: Get Current State\n";
echo "-----------------------------------------\n";
$state = $stateMachine->getCurrentState($contractId);
echo "Current state: " . ($state ?: 'null') . "\n\n";

echo "-----------------------------------------\n";
echo "Test 2: Get Next State\n";
echo "-----------------------------------------\n";
$nextState = $stateMachine->getNextState($currentState);
echo "From '{$currentState}' next state is: " . ($nextState ?: 'none') . "\n\n";

echo "-----------------------------------------\n";
echo "Test 3: Check if can transition\n";
echo "-----------------------------------------\n";
$canTransition = $stateMachine->canTransition($contractId, $currentState);
echo "Can transition from '{$currentState}': " . ($canTransition ? 'YES' : 'NO') . "\n\n";

echo "-----------------------------------------\n";
echo "Test 4: Perform Transition\n";
echo "-----------------------------------------\n";
$result = $stateMachine->transition($contractId, $currentState, 1);

echo "Result:\n";
print_r($result);

if ($result['success']) {
    echo "\n✓ Transition successful!\n";
    echo "New state: " . $result['new_state'] . "\n";
    
    // Verify the change in database
    $verify = $db->query("SELECT signing_state FROM contracts WHERE id = {$contractId}")->fetch();
    echo "Verified in database: " . $verify['signing_state'] . "\n";
} else {
    echo "\n✗ Transition failed: " . $result['error'] . "\n";
}

echo "\n=========================================\n";
echo "Test Complete\n";
echo "=========================================\n";
echo "</pre>";