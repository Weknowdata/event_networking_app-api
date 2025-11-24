<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use RuntimeException;

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
     * @var list<string>
     */
    protected $appends = [
        'qr_payload',
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
        ];
    }

    public static function buildName(string $firstName, string $lastName): string
    {
        return trim(sprintf('%s %s', $firstName, $lastName));
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function qrSignature(): string
    {
        $key = config('app.key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('Application key is missing; cannot sign QR payloads.');
        }

        return hash_hmac('sha256', (string) $this->getAuthIdentifier(), $key);
    }

    public function qrPayload(): string
    {
        $payload = [
            'attendee_id' => (string) $this->getAuthIdentifier(),
            'name' => $this->name,
            'email' => $this->email,
            'sig' => $this->qrSignature(),
        ];

        return json_encode($payload, JSON_UNESCAPED_SLASHES);
    }

    public function getQrPayloadAttribute(): string
    {
        return $this->qrPayload();
    }
}
