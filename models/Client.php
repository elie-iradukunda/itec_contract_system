<?php

namespace Models;

use Core\Database;
use PDO;

class Client
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll()
    {
        $sql = "SELECT c.*, u.email, u.name as user_name 
                FROM clients c 
                JOIN users u ON c.user_id = u.id 
                ORDER BY c.created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $sql = "SELECT c.*, u.email, u.name as user_name 
                FROM clients c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByUserId($userId)
    {
        $sql = "SELECT * FROM clients WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $sql = "INSERT INTO clients (user_id, company_name, contact_person, phone, email, address, tax_id, registration_number) 
                VALUES (:user_id, :company_name, :contact_person, :phone, :email, :address, :tax_id, :registration_number)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $data['user_id'],
            'company_name' => $data['company_name'] ?? null,
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'registration_number' => $data['registration_number'] ?? null
        ]);
    }

    public function update($id, $data)
    {
        $sql = "UPDATE clients SET 
                    company_name = :company_name,
                    contact_person = :contact_person,
                    phone = :phone,
                    email = :email,
                    address = :address,
                    tax_id = :tax_id,
                    registration_number = :registration_number,
                    status = :status
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'company_name' => $data['company_name'] ?? null,
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'registration_number' => $data['registration_number'] ?? null,
            'status' => $data['status'] ?? 'active'
        ]);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM clients WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function getClientContracts($clientId)
    {
        $sql = "SELECT * FROM contracts WHERE client_id = :client_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveClients()
    {
        $sql = "SELECT c.*, u.email 
                FROM clients c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.status = 'active'";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}