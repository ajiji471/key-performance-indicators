<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Registered = 'registered';
    case LoggedIn = 'logged_in';
    case Recovered = 'recovered';
    case Trouble = 'trouble';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Registered',
            self::LoggedIn => 'Logged in',
            self::Recovered => 'Recovered',
            self::Trouble => 'Trouble',
        };
    }
}