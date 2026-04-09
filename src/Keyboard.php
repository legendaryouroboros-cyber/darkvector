<?php

namespace src;

class Keyboard
{
    public static function showRole(): array
    {
        return [
            'inline_keyboard' => [
                [['text' => 'Показать роль', 'callback_data' => 'show_role']]
            ]
        ];
    }

    public static function done(): array
    {
        return [
            'inline_keyboard' => [
                [['text' => 'Готов', 'callback_data' => 'done']
             ]
            ]
        ];
    }

    public static function themeChoice(): array
    {
        $themes = require __DIR__ . '/../data/themes.php';
        $buttons = [];
        foreach ($themes as $key => $words) {
            $buttons[] = [['text' => $key, 'callback_data' => 'theme_' . $key]];
        }
        return ['inline_keyboard' => $buttons];
    }
}