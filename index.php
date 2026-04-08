<?php

$token = '8704126137:AAFKWtfeYJq2A9LVAlJOoM-nMAnmzVhFGhY';
$botUrl = "https://api.telegram.org/bot{$token}";

// Пробуем все способы получить данные
$input = file_get_contents('php://input');
$raw = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : '';

file_put_contents('/tmp/log.txt',
    date('H:i:s') .
    "\nINPUT: " . $input .
    "\nRAW: " . $raw .
    "\nPOST: " . json_encode($_POST) .
    "\nSERVER: " . json_encode($_SERVER) .
    "\n---\n",
    FILE_APPEND);

$update = json_decode($input, true);

if (!isset($update['message'])) {
    echo file_get_contents('/tmp/log.txt');
    exit;
}

$chatId = $update['message']['chat']['id'];
$text = $update['message']['text'] ?? '';

$reply = $text === '/start' ? 'Привет! Я работаю 🤖' : 'Ты написал: ' . $text;

$data = json_encode(['chat_id' => $chatId, 'text' => $reply]);
$ch = curl_init("{$botUrl}/sendMessage");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_exec($ch);
curl_close($ch);

http_response_code(200);