<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Export;

use App\Models\User;

readonly class ExportedCreatorV1
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}

    /**
     * Create from User model.
     */
    public static function fromModel(User $user): self
    {
        return new self(
            name: $user->name,
            email: $user->email,
        );
    }

    /**
     * Create from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
} 