<?php

namespace App\Enums;

enum ActivationStatus: string
{
    case Active = 'active';
    case Locked = 'locked';
}
