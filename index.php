<?php

$token = '8704126137:AAFKWtfeYJq2A9LVAlJOoM-nMAnmzVhFGhY';
$botUrl = "https://api.telegram.org/bot{$token}";

$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!isset($update['message'])) exit;

$chatId = $update['message']['chat']['id'];
$text = $update['message']['text'] ?? '';

if ($text === '/start') {
    $reply = 'Привет! Я работаю 🤖';
} elseif ($text === '/help') {
    $reply = 'Доступные команды: /start, /help';
} else {
    $reply = 'Ты написал: ' . $text;
}

$data = json_encode([
    'chat_id' => $chatId,
    'text' => $reply
]);

$ch = curl_init("{$botUrl}/sendMessage");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_exec($ch);
curl_close($ch);

http_response_code(200);
