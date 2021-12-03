<?php

namespace IvanMeshcheryakov\Tgbot;

use mysqli;

class DB
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = new mysqli("localhost", "root", "", "tgbot_db");
    }


    public function doesUserExists($chatId): bool
    {
        $result = $this->db->query("SELECT name FROM users WHERE chat_id LIKE '{$chatId}'");
        $row = $result->fetch_row();

        if (!$row) {
            return false;
        }
        return true;
    }

    public function addNewUser(string $chatId, string $name):bool
    {
        return $this->db->query("INSERT INTO users (chat_id, name) VALUES ('{$chatId}', '{$name}')");
    }
}