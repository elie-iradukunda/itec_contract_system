<?php

namespace Controllers;

use Core\Controller;

class ClientController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function portal()
    {
        $this->view('clients/portal', [
            'title' => 'Client Portal'
        ]);
    }

    public function show($id)
    {
        $this->view('clients/show', [
            'title' => 'Client Details',
            'client_id' => $id
        ]);
    }

    public function index()
    {
        $this->view('clients/index', [
            'title' => 'All Clients'
        ]);
    }
}