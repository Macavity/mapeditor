<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    type: 'object',
    properties: [
        'id' => new OA\Property(property: 'id', type: 'integer', example: 1),
        'name' => new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        'email' => new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        'is_admin' => new OA\Property(property: 'is_admin', type: 'boolean', example: false),
        'email_verified_at' => new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
        'created_at' => new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        'updated_at' => new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }
}
