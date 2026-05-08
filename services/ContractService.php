<?php

namespace Services;

use Models\Contract;

class ContractService
{
    private $contractModel;

    public function __construct(Contract $contractModel)
    {
        $this->contractModel = $contractModel;
    }

    public function getAllContracts()
    {
        return $this->contractModel->findAll();
    }

    public function getContractById($id)
    {
        return $this->contractModel->find($id);
    }
}
