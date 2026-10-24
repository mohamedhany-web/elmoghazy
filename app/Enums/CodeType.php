<?php

namespace App\Enums;

enum CodeType: string
{
    case Course = 'course';
    case Month = 'month';
    case Lesson = 'lesson';
    case Relock = 'relock';
    case Parent = 'parent';
}
