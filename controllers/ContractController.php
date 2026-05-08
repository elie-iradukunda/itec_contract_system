<?php

namespace Controllers;

use Core\Controller;

class ContractController extends Controller
{
    private $contractService;

    public function __construct($contractService = null)
    {
        parent::__construct();
        $this->contractService = $contractService;
    }

    public function index()
    {
        $this->view('contracts/index', [
            'title' => 'Contracts'
        ]);
    }

    public function show($id)
    {
        $this->json([
            'success' => true,
            'contract_id' => $id,
            'message' => 'Contract found'
        ]);
    }

    // 


    public function transition($id)
{
    session_start();
    $userId = $_SESSION['user_id'] ?? null;
    
    $stateMachine = new \Services\OscarStateMachineService();
    $currentState = $stateMachine->getCurrentState($id);
    
    if (!$currentState) {
        $this->json(['success' => false, 'error' => 'Contract not found'], 404);
        return;
    }
    
    $result = $stateMachine->transition($id, $currentState, $userId);
    $this->json($result);
}

public function getState($id)
{
    $stateMachine = new \Services\OscarStateMachineService();
    $state = $stateMachine->getCurrentState($id);
    $nextState = $stateMachine->getNextState($state);
    
    $this->json([
        'contract_id' => $id,
        'current_state' => $state,
        'next_state' => $nextState,
        'can_transition' => ($nextState !== null)
    ]);
}

public function submitForSigning($id)
{
    session_start();
    $userId = $_SESSION['user_id'] ?? null;
    
    $stateMachine = new \Services\OscarStateMachineService();
    $currentState = $stateMachine->getCurrentState($id);
    
    if ($currentState !== 'DRAFT') {
        $this->json(['success' => false, 'error' => 'Contract must be in DRAFT state to submit'], 400);
        return;
    }
    
    $result = $stateMachine->transition($id, $currentState, $userId);
    $this->json($result);
}


}
