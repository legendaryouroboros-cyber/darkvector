<?php

namespace src;

use Roles;

class Game
{
    private array $players = [];
    private string $theme;
    private string $word;
    private int $spyCount;

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): void
    {
        $this->theme = $theme;
    }

    public function getWord(): string
    {
        return $this->word;
    }

    public function setWord(string $word): void
    {
        $this->word = $word;
    }

    public function getSpyCount(): int
    {
        return $this->spyCount;
    }

    public function setSpyCount(int $spyCount): void
    {
        $this->spyCount = $spyCount;
    }



    public function create(int $playerCount, int $spyCount, string $theme): void
    {
        $this->spyCount = $spyCount;
        $this->theme = $theme;

        $themes = require __DIR__ . '/../data/themes.php';
        $this->word = $themes[$theme][array_rand($themes[$theme])];
        $roles = array_fill(0, $spyCount, Roles::Spy);
        $roles = array_merge($roles, array_fill(0,  $playerCount - $spyCount, Roles::NotSpy));

        shuffle($roles);
        $this->players = $roles;
    }


    public function getRole(int $playerIndex): string
    {
        if (!isset($this->players[$playerIndex])) {
            return "";
        }

        if ($this->players[$playerIndex] === Roles::Spy) {
            return "Шпион";
        }

        return $this->word;

    }

    public function getPlayers(): array
    {
        return $this->players;
    }


}