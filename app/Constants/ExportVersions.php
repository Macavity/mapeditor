<?php

declare(strict_types=1);

namespace App\Constants;

class ExportVersions
{
    public const V1_0 = '1.0';
    public const CURRENT = self::V1_0;

    /**
     * Get all supported export versions.
     */
    public static function getSupportedVersions(): array
    {
        return [
            self::V1_0,
        ];
    }

    /**
     * Check if a version is supported.
     */
    public static function isSupported(string $version): bool
    {
        return in_array($version, self::getSupportedVersions());
    }

    /**
     * Get the current export version.
     */
    public static function getCurrent(): string
    {
        return self::CURRENT;
    }

    /**
     * Get version info for display.
     */
    public static function getVersionInfo(): array
    {
        return [
            self::V1_0 => [
                'version' => self::V1_0,
                'description' => 'Initial versioned export format with DTO structure',
                'features' => [
                    'Structured map metadata',
                    'Complete tileset information',
                    'Layer data with type safety',
                    'Creator information',
                    'Timestamp tracking',
                ],
            ],
        ];
    }
} 