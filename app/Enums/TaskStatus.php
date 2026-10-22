<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Registered = 'registered';
    case LoggedIn = 'logged_in';
    case Trouble = 'trouble';
}
