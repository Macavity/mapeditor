<?php declare(strict_types=1);

namespace App\ValueObjects;

final class Tile
{
    public function __construct(
        public int $x,
        public int $y,
        public Brush $brush,
    ) {}
}

final class Brush
{
    public function __construct(
        public string $tileset,
        public int $tileX,
        public int $tileY,
    ) {}
}
