<?php
include_once 'vendor/autoload.php';

use GuzzleHttp\Client;

class Main
{
    private $apiUrl;
    private $db;

    public function __construct()
    {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();

        $this->apiUrl = "https://api.telegram.org/bot" . $_ENV['TG_TOKEN'];
        $this->db = new mysqli("localhost", "root", "", "tgbot_db");
    }

    public function main()
    {
        $updateId = null;
        $offset = ($updateId ?? 0) + 1;

        while (true) {
            $resp = $this->makeRequest("getUpdates", ['offset' => $offset]);
            // Получаю массив Update
            foreach ($resp->result as $update) {
                if (!isset($update->message)) {
                    continue;
                }
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
                                $messageText = "{$name}, бот запущен!";
                                $this->makeRequest("sendMessage", ['chat_id' => $chatId, 'text' => $messageText]);
                                break;
                        }
                    }
                }
                if (isset($update->message->text) && $update->message->text == 'Пидар') {
                    $this->makeRequest("pinChatMessage", ['chat_id' => $chatId, 'message_id' => $update->message->message_id]);
                }
                $updateId = $update->update_id;
            }
            $offset = $updateId + 1;
        }
    }

    public function makeRequest($methodName, $query = [])
    {
        $client = new Client();
        $response = $client->request('GET', "$this->apiUrl/$methodName", [
            'query' => $query,
        ]);
        return json_decode($response->getBody()->getContents());
    }
}

(new Main())->main();