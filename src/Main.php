<?php
namespace IvanMeshcheryakov\Tgbot;

use GuzzleHttp\Client;
use Dotenv\Dotenv;

class Main
{
    private string $apiUrl;
    private DB $db;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__.'/../');
        $dotenv->load();

        $this->apiUrl = "https://api.telegram.org/bot" . $_ENV['TG_TOKEN'];
        $this->db = new DB();
    }

    public function main()
    {
        $updateId = null;
        $offset = ($updateId ?? 0) + 1;

        while (true) {
            $resp = $this->makeRequest("getUpdates", ['offset' => $offset]);

            if (!$resp) {
                $offset = $updateId + 1;
                continue;
            }
            // Получаю массив Update
            foreach ($resp->result as $update) {
                switch ($update) {
                    case (isset($update->message)):
                        print "Пришло сообщение\n";
                        $this->processMessageCase($update);
                        break;
                    case (isset($update->inline_query)):
                        print "Пришел инлайн запрос\n";
                        break;
                    case (isset($update->my_chat_member)):
                        print "Обновлен статус бота в чате.\n";
                        break;
                }
                $updateId = $update->update_id;
            }
            $offset = $updateId + 1;
        }
    }

    public function makeRequest($methodName, $query = [])
    {
        $client = new Client();
        try {
            $response = $client->request('GET', "$this->apiUrl/$methodName", [
                'query' => $query,
            ]);
        } catch (\Exception $exception) {
            print_r($exception->getMessage());
            return false;
        }

        return json_decode($response->getBody()->getContents());
    }

    private function processMessageCase($update)
    {
        $chatId = $update->message->chat->id;
        // Определяю имя
        $name = isset($update->message->chat->first_name) ? $update->message->chat->first_name : 'none';

        if (!$this->db->doesUserExists($chatId)) {
            print_r("Новый пользователь\n");
            $result = $this->db->addNewUser($chatId, $name);

            if (!$result) {
                print "Ошибка при добавлении нового пользователя!\n";
            }
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
        if (isset($update->message->text)) {
            print "{$update->message->text}\n";

            if ($update->message->text == 'роза') {
                $this->makeRequest("pinChatMessage", ['chat_id' => $chatId, 'message_id' => $update->message->message_id]);

            }
        }
    }
}