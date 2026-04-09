<?php

namespace src;

class Bot
{
    private string $token;
    private Game $game;
    private array $state = [];
    private string $supabaseUrl = 'https://hxgemkwwvsgvmzfdsrjv.supabase.co';
    private string $supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imh4Z2Vta3d3dnNndm16ZmRzcmp2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzU3NDEyNzYsImV4cCI6MjA5MTMxNzI3Nn0.0NIV1g0hpEvy44g-xD1NUw4-5HNO_xXe62crBPJ_chY';
    public function __construct(Game $game, string $token)
    {
        $this->game = $game;
        $this->token = $token;
    }

    public function handle(array $update): void
    {
        $chatId = $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'];
        $this->loadState($chatId);

        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        }

        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
        }

        $this->saveState($chatId);
    }


    private function loadState(string $chatId): void
    {
        $ch = curl_init($this->supabaseUrl . '/rest/v1/bot_state?chat_id=eq.' . $chatId);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $this->supabaseKey,
                'Authorization: Bearer ' . $this->supabaseKey,
            ],
        ]);
        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!empty($result)) {
            $row = $result[0];
            $this->state[$chatId] = $row['state'];
            $this->state[$chatId . '_players'] = $row['players'];
            $this->state[$chatId . '_spies'] = $row['spies'];
            $this->state[$chatId . '_current'] = $row['current'];
            $this->state[$chatId . '_word'] = $row['word'];
            $this->state[$chatId . '_roles'] = json_decode($row['roles'], true);
        }
    }

    private function saveState(string $chatId): void
    {
        $data = json_encode([
            'chat_id' => $chatId,
            'state' => $this->state[$chatId] ?? null,
            'players' => $this->state[$chatId . '_players'] ?? null,
            'spies' => $this->state[$chatId . '_spies'] ?? null,
            'current' => $this->state[$chatId . '_current'] ?? 0,
            'word' => $this->state[$chatId . '_word'] ?? null,
            'roles' => json_encode($this->state[$chatId . '_roles'] ?? []),
        ]);

        $ch = curl_init($this->supabaseUrl . '/rest/v1/bot_state');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $this->supabaseKey,
                'Authorization: Bearer ' . $this->supabaseKey,
                'Content-Type: application/json',
                'Prefer: resolution=merge-duplicates',
            ],
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    private function handleMessage(array $message): void
    {
        $text = $message['text'] ?? '';
        $chatId = $message['chat']['id'];
        $messageId = $message['message_id'];

        if ($text === '/start') {
            $this->onStart($chatId);
            $this->deleteMessage($chatId, $messageId);
            return;
        }

        $currentState = $this->state[$chatId] ?? null;

        match($currentState) {
            'waiting_players' => $this->onWaitingPlayers($chatId, $text),
            'waiting_spies' => $this->onWaitingSpies($chatId, $text),
            default => null
        };

        $this->deleteMessage($chatId, $messageId);
    }

    private function handleCallback(array $callback): void
    {
        $data = $callback['data'];
        $chatId = $callback['message']['chat']['id'];
        $messageId = $callback['message']['message_id'];

        $this->deleteMessage($chatId, $messageId);

        if (str_starts_with($data, 'theme_')) {
            $theme = str_replace('theme_', '', $data);
            $players = $this->state[$chatId . '_players'];
            $spies = $this->state[$chatId . '_spies'];

            $this->game->create($players, $spies, $theme);

            $this->state[$chatId . '_word'] = $this->game->getWord();
            $this->state[$chatId . '_roles'] = $this->game->getPlayers();
            $this->state[$chatId] = 'playing';
            $this->state[$chatId . '_current'] = 0;

            $this->sendMessage($chatId, 'Игра началась!', Keyboard::showRole());
            return;
        }

        match($data) {
            'show_role' => $this->onShowRole($chatId),
            'done' => $this->onDone($chatId),
            default => null
        };
    }

    private function onStart(string $chatId): void
    {
        $this->state[$chatId] = 'waiting_players';
        $this->sendMessage($chatId,'Добро пожаловать в класс шпиона. Сколько игроков?');

    }

    private function sendMessage(string $chatId, string $text, array $keyboard = []): void
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if (!empty($keyboard)) {
            $params['reply_markup'] = json_encode($keyboard);
        }

        $ch = curl_init('https://api.telegram.org/bot' . $this->token . '/sendMessage');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $params,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    private function onWaitingPlayers(string $chatId, string $text): void
    {
        if (!is_numeric($text) || (int)$text < 2) {
            $this->sendMessage($chatId, 'Введи число игроков (минимум 2)');
            return;
        }

        $this->state[$chatId] = 'waiting_spies';
        $this->state[$chatId . '_players'] = (int)$text;
        $this->sendMessage($chatId, 'Сколько шпионов?');
    }

    private function onWaitingSpies(string $chatId, string $text): void
    {
        if (!is_numeric($text) || (int)$text < 1) {
            $this->sendMessage($chatId, 'Введи количество шпионов (минимум 1)');
            return;
        }
        $this->state[$chatId . '_spies'] = (int)$text;
        $this->sendMessage($chatId, 'Выбери тему:', Keyboard::themeChoice());
    }

    private function onShowRole(string $chatId): void
    {
        $index = $this->state[$chatId . '_current'];
        $roles = $this->state[$chatId . '_roles'];
        $word = $this->state[$chatId . '_word'];

        $role = $roles[$index];
        $text = ($role === 'Шпион') ? 'Шпион' : $word;

        $this->sendMessage($chatId, $text, Keyboard::done());
    }

    private function onDone(string $chatId): void
    {
        $current = $this->state[$chatId . '_current'];
        $total = $this->state[ $chatId . '_players'];

        $current++;
        $this->state[$chatId . '_current'] = $current;
        if ($current >= $total) {
            $this->sendMessage($chatId, 'Все роли розданы!');
        } else {
            $this->sendMessage($chatId, 'Передай телефон следующему игроку', Keyboard::showRole());
        }
    }



    private function deleteMessage(string $chatId, int $messageId): void
    {
        $ch = curl_init('https://api.telegram.org/bot' . $this->token . '/deleteMessage');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ],
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}