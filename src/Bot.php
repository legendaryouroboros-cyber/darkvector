<?php

namespace src;

class Bot
{
    private string $token;
    private Game $game;

    private array $state = [];

    public function __construct(Game $game, string $token)
    {
        $this->game = $game;
        $this->token = $token;
    }

    public function handle(array $update): void
    {
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        }

        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
        }
    }

    private function handleMessage(array $message): void
    {
        $text = $message['text'] ?? '';
        $chatId = $message['chat']['id'];

        if ($text === '/start') {
            $this->onStart($chatId);
            return;
        }

        $currentState = $this->state[$chatId] ?? null;

        match($currentState) {
            'waiting_players' => $this->onWaitingPlayers($chatId, $text),
            'waiting_spies' => $this->onWaitingSpies($chatId, $text),
            default => null
        };
}

    private function handleCallback(array $callback): void
    {
        $data = $callback['data'];
        $chatId = $callback['message']['chat']['id'];

        if (str_starts_with($data, 'theme_')) {
            $theme = str_replace('theme_', '', $data);
            $players = $this->state[$chatId . '_players'];
            $spies = $this->state[$chatId . '_spies'];

            $this->game->create($players, $spies, $theme);
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
        $this->sendMessage($chatId,'Добро пожаловать в класс шпиона');
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
        $role = $this->game->getRole($index);

        $this->sendMessage($chatId, $role, Keyboard::done());
    }

    private function onDone(string $chatId): void
    {
        $current = $this->state[$chatId . '_current'];
        $total = $this->state[$chatId . '_players'];

        $current++;
        $this->state[$chatId . '_current'] = $current;
        if ($current >= $total) {
            $this->sendMessage($chatId, 'Все роли розданы!');
        } else {
            $this->sendMessage($chatId, 'Передай телефон следующему игроку', Keyboard::showRole());
        }
    }

}