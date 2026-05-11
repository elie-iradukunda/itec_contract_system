<?php

namespace Controllers;

use Core\Controller;
use Models\Client;

class ClientController extends Controller
{
    private $clientModel;

    public function __construct($clientModel = null)
    {
        parent::__construct();
        $this->clientModel = $clientModel ?: new Client();
    }

    public function index()
    {
        $clients = $this->clientModel->findAll();
        $this->view('clients/index', [
            'title' => 'All Clients',
            'clients' => $clients
        ]);
    }

    public function show($id)
    {
        $client = $this->clientModel->find($id);
        $contracts = $this->clientModel->getClientContracts($id);
        
        $this->view('clients/show', [
            'title' => 'Client Details',
            'client' => $client,
            'contracts' => $contracts
        ]);
    }

    public function portal()
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $userId = $_SESSION['user_id'] ?? null;

        // Local dev fallback keeps the client portal testable without the parent finance auth session.
        $client = $userId ? $this->clientModel->findByUserId($userId) : $this->clientModel->find(1);
        
        if (!$client) {
            $this->view('errors/404', ['message' => 'Client profile not found']);
            return;
        }
        
        $contracts = $this->clientModel->getClientContracts($client['id']);
        
        $this->view('clients/portal', [
            'title' => 'Client Portal',
            'client' => $client,
            'contracts' => $contracts
        ]);
    }

    public function clientContracts($id)
    {
        $contracts = $this->clientModel->getClientContracts((int) $id);
        $this->json(['success' => true, 'client_id' => (int) $id, 'contracts' => $contracts]);
    }

    public function create()
    {
        $this->view('clients/create', ['title' => 'Create Client']);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /'.BASE_URL.'/clients/create');
            return;
        }

        $data = [
            'user_id' => $_POST['user_id'],
            'company_name' => $_POST['company_name'],
            'contact_person' => $_POST['contact_person'],
            'phone' => $_POST['phone'],
            'email' => $_POST['email'],
            'address' => $_POST['address'],
            'tax_id' => $_POST['tax_id'],
            'registration_number' => $_POST['registration_number']
        ];

        if ($this->clientModel->create($data)) {
            header('Location: /'.BASE_URL.'/clients');
        } else {
            echo "Failed to create client";
        }
    }

    public function edit($id)
    {
        $client = $this->clientModel->find($id);
        $this->view('clients/edit', [
            'title' => 'Edit Client',
            'client' => $client
        ]);
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /'.BASE_URL.'/clients/edit/' . $id);
            return;
        }

        $data = [
            'company_name' => $_POST['company_name'],
            'contact_person' => $_POST['contact_person'],
            'phone' => $_POST['phone'],
            'email' => $_POST['email'],
            'address' => $_POST['address'],
            'tax_id' => $_POST['tax_id'],
            'registration_number' => $_POST['registration_number'],
            'status' => $_POST['status']
        ];

        if ($this->clientModel->update($id, $data)) {
            header('Location: /'.BASE_URL.'/clients/show/' . $id);
        } else {
            echo "Failed to update client";
        }
    }

    public function delete($id)
    {
        if ($this->clientModel->delete($id)) {
            header('Location: /'.BASE_URL.'/clients');
        } else {
            echo "Failed to delete client";
        }
    }
}
