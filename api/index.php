<?php
$token = '8570074343:AAFg9TY4HpzIAzH75H5FFV_Nc4W72wW2u_Q';
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) exit;

$chat_id = $update['message']['chat']['id'] ?? null;
$text = $update['message']['text'] ?? '';

if ($chat_id && $text) {
    $reply = 'Ты написал: ' . $text;
    
    $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode([
            'chat_id' => $chat_id,
            'text' => $reply
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);
    curl_exec($ch);
    curl_close($ch);
}