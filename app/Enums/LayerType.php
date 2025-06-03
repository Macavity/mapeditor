<?php

declare(strict_types=1);

namespace App\Enums;

enum LayerType: string
{
    case Background = 'background';
    case Floor = 'floor';
    case Sky = 'sky';
    case FieldType = 'field_type';
} 