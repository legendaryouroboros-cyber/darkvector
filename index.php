<?php

$token = '8704126137:AAFKWtfeYJq2A9LVAlJOoM-nMAnmzVhFGhY';
$botUrl = "https://api.telegram.org/bot{$token}";

$input = file_get_contents('php://input');
error_log("INPUT: " . $input);

$update = json_decode($input, true);

if (!isset($update['message'])) {
    echo "OK";
    exit;
}

$chatId = $update['message']['chat']['id'];
$text = $update['message']['text'] ?? '';

if ($text === '/start') {
    $reply = 'Привет! Я работаю 🤖';
} else {
    $reply = 'Ты написал: ' . $text;
}

$data = json_encode(['chat_id' => $chatId, 'text' => $reply]);
$ch = curl_init("{$botUrl}/sendMessage");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$result = curl_exec($ch);
error_log("RESULT: $result");
curl_close($ch);

http_response_code(200);