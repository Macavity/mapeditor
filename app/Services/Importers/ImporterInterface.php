<?php

declare(strict_types=1);

namespace App\Services\Importers;

interface ImporterInterface
{
    /**
     * Parse a map file and return structured data.
     *
     * @param string $filePath Path to the file to parse
     * @return array Structured map data
     * @throws \InvalidArgumentException If the file cannot be parsed
     */
    public function parse(string $filePath): array;

    /**
     * Parse raw data string and return structured data.
     *
     * @param string $data Raw data string to parse
     * @return array Structured map data
     * @throws \InvalidArgumentException If the data cannot be parsed
     */
    public function parseString(string $data): array;

    /**
     * Check if this importer can handle the given file.
     *
     * @param string $filePath Path to the file to check
     * @return bool True if this importer can handle the file
     */
    public function canHandle(string $filePath): bool;

    /**
     * Get the supported file extensions for this importer.
     *
     * @return array Array of supported file extensions (without dots)
     */
    public function getSupportedExtensions(): array;

    /**
     * Get a human-readable name for this importer.
     *
     * @return string Name of the importer
     */
    public function getName(): string;

    /**
     * Get a description of what this importer handles.
     *
     * @return string Description of the importer
     */
    public function getDescription(): string;
} 