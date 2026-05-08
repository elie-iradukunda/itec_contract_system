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
}
