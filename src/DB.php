<?php

namespace IvanMeshcheryakov\Tgbot;

use mysqli;

class DB
{
    public mysqli $db;

    public function __construct()
    {
        $this->db = new mysqli("localhost", "root", "", "tgbot_db");
    }
}