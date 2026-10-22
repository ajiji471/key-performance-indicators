<?php

namespace App\Enums;


enum TaskProgress: string
{
    case Ongoing = 'ongoing';
    case Done = 'done';
    case Pending = 'pending';
}
