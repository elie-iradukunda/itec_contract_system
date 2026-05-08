<?php

namespace Core;

abstract class Migration
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    abstract public function up();
    abstract public function down();
}
