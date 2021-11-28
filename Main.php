<?php

class Main
{
    const TOKEN = '2137836088:AAE7pMqCqMnJmKZIRfbMuhYOUhUsSyldPPU';

    private $apiUrl;
    private $db;

    public function __construct()
    {
        $this->apiUrl = "https://api.telegram.org/bot" . $this::TOKEN . "/";
        $this->db = new mysqli("localhost", "root", "", "tgbot_db");
    }

    public function main()
    {
        $updateId = null;
        $offset = ($updateId ?? 0) + 1;

        while (true) {
            $resp = $this->makeRequest("getUpdates?offset={$offset}");
            // Получаю массив Update
            foreach ($resp->result as $update) {
                $chatId = $update->message->chat->id;
                // Определяю имя
                $name = isset($update->message->chat->first_name) ? $update->message->chat->first_name : 'none';

                $dbRes = $this->db->query("SELECT name FROM users WHERE chat_id LIKE '{$chatId}'");

                if ($this->db->affected_rows == 0) {
                    $dbRes = $this->db->query("INSERT INTO users(chat_id, name) VALUES ('{$chatId}', '{$name}')");
                }

                // Проверка на бот комманду или другую сущность
                if (isset($update->message->entities)) {
                    $isBotCommand = false;

                    foreach ($update->message->entities as $entity) {
                        if ($entity->type == 'bot_command') {
                            $isBotCommand = true;
                            break;
                        }
                    }
                    if ($isBotCommand) {
                        switch ($update->message->text) {
                            case '/start':
                                $messageText = urlencode('Бот запущен! ' . $name);
                                $this->makeRequest("sendMessage?chat_id={$chatId}&text={$messageText}");
                                break;
                        }
                    }
                }
                $updateId = $update->update_id;
            }
            $offset = $updateId + 1;
        }
    }

    public function makeRequest($methodName)
    {
        $response = file_get_contents($this->apiUrl . $methodName);
        return json_decode($response);
    }
}

(new Main())->main();