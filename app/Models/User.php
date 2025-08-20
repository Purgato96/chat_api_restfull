<?php

/**
 * Modelo de usuário estendido com suporte
 * a tokens de API e relacionamentos do chat.
 */

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'account_id',
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
        ];
    }
    // --- Relacionamentos do Chat ---
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class)
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    public function createdRooms(): HasMany
    {
        return $this->hasMany(Room::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    // --- Métodos para Tokens (Sanctum) ---
    /**
     * Cria token com habilidades específicas para chat
     */
    public function createChatToken(string $deviceName): string
    {
        return $this->createToken($deviceName, [
            'chat:read',
            'chat:write',
            'chat:join',
            'chat:leave'
        ])->plainTextToken;
    }

    /**
     * Verifica se o usuário tem permissão específica
     */
    public function canChat(string $ability): bool
    {
        $token = $this->currentAccessToken();

        if (!$token) {
            return false;
        }

        return $token->can($ability);
    }

    /**
     * Lista tokens ativos do usuário
     */
    public function activeTokens()
    {
        return $this->tokens()
            ->where('last_used_at', '>', now()->subDays(30))
            ->orWhereNull('last_used_at')
            ->get();
    }

    public function privateConversationsAsUserOne()
    {
        return $this->hasMany(PrivateConversation::class, 'user_one_id');
    }

    public function privateConversationsAsUserTwo()
    {
        return $this->hasMany(PrivateConversation::class, 'user_two_id');
    }

    public function privateConversations()
    {
        return $this->privateConversationsAsUserOne()
            ->union($this->privateConversationsAsUserTwo());
    }

    public function sentPrivateMessages()
    {
        return $this->hasMany(PrivateMessage::class, 'sender_id');
    }

}
