# Sistema de Chat Completo - Instalação do Zero

Este guia detalhado orienta você na instalação e configuração completa do sistema de chat, incluindo o backend Laravel (com API REST) e o frontend Vue.js com Inertia, partindo do absoluto zero na sua máquina local.

## 1. Pré-requisitos

Antes de começar, certifique-se de ter os seguintes softwares instalados e configurados no seu ambiente de desenvolvimento:

- **PHP 8.1 ou superior:** Com as extensões `mbstring`, `xml`, `ctype`, `json`, `tokenizer`, `bcmath`, `pdo_mysql` (ou o driver do seu banco de dados).
- **Composer:** Gerenciador de dependências para PHP. [https://getcomposer.org/](https://getcomposer.org/)
- **Node.js e npm:** Para gerenciar as dependências do frontend e compilar os assets. [https://nodejs.org/](https://nodejs.org/)
- **Servidor de Banco de Dados:** MySQL, PostgreSQL ou SQLite. Este guia usará MySQL como exemplo.
- **Git:** Para controle de versão (opcional, mas recomendado).
- **Conta no Pusher:** Para o serviço de WebSockets. Crie uma conta gratuita em [https://pusher.com/](https://pusher.com/).

## 2. Criando o Projeto Laravel

Se você ainda não tem um projeto Laravel, crie um novo:

```bash
composer create-project laravel/laravel sistema-chat
cd sistema-chat
```

Se você já tem um projeto Laravel, pode pular este passo e adaptar os comandos para o seu diretório existente.

## 3. Configuração Inicial do Laravel

### 3.1. Arquivo `.env`

Copie o arquivo `.env.example` para `.env` se ele ainda não existir:

```bash
cp .env.example .env
```

Abra o arquivo `.env` e configure as seguintes seções:

#### 3.1.1. Informações da Aplicação

```env
APP_NAME="Sistema de Chat"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000 # Ou o URL que você usará para desenvolvimento
```

Se `APP_KEY` estiver vazio, gere uma nova chave:

```bash
php artisan key:generate
```

#### 3.1.2. Configuração do Banco de Dados (Exemplo com MySQL)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_chat # Crie este banco de dados no seu MySQL
DB_USERNAME=root # Seu usuário do MySQL
DB_PASSWORD= # Sua senha do MySQL
```

Certifique-se de criar o banco de dados `sistema_chat` (ou o nome que você escolheu) no seu servidor MySQL.

#### 3.1.3. Configuração do Pusher

Obtenha suas credenciais (App ID, Key, Secret, Cluster) no painel do Pusher após criar seu aplicativo.

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=SEU_PUSHER_APP_ID
PUSHER_APP_KEY=SUA_PUSHER_APP_KEY
PUSHER_APP_SECRET=SEU_PUSHER_APP_SECRET
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=SEU_PUSHER_APP_CLUSTER # Ex: mt1, eu, ap1

# Configurações do Vite para Pusher (serão usadas pelo frontend)
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

#### 3.1.4. Configuração do Sanctum e CORS (para a API)

```env
# Domínios que podem usar autenticação stateful (SPA no mesmo domínio)
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1,seu-dominio-frontend.com

# Domínios permitidos para CORS (separados por vírgula)
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:8080,http://localhost:5173,https://seu-dominio-frontend.com

# Configurações adicionais da API
API_RATE_LIMIT=60
API_RATE_LIMIT_AUTHENTICATED=120
PUSHER_ALLOW_EXTERNAL_AUTH=true
API_REQUIRE_HTTPS=false # Mude para true em produção
API_ENABLE_RATE_LIMITING=true
```

### 3.2. Instalar Dependências do Laravel

```bash
# Laravel Sanctum (para autenticação de API e SPA)
composer require laravel/sanctum

# Pusher PHP Server (para broadcasting)
composer require pusher/pusher-php-server

# Laravel CORS (para permitir requisições de outros domínios para a API)
composer require fruitcake/laravel-cors

# Inertia.js (para o frontend Vue.js)
composer require inertiajs/inertia-laravel

# Laravel Jetstream (opcional, mas recomendado para scaffolding de autenticação)
# Se for usar Jetstream, instale ANTES de configurar o frontend manualmente
# composer require laravel/jetstream
# php artisan jetstream:install inertia --teams (ou sem --teams)
# npm install
# npm run build
# php artisan migrate
```

### 3.3. Publicar Arquivos de Configuração

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --tag=cors # Para o fruitcake/laravel-cors
php artisan inertia:middleware # Cria o middleware HandleInertiaRequests
```

Após publicar o middleware do Inertia, registre-o em `app/Http/Kernel.php` no grupo `web`:

```php
// Dentro de app/Http/Kernel.php

protected $middlewareGroups = [
    'web' => [
        // ... outros middlewares
        \App\Http\Middleware\HandleInertiaRequests::class, // Adicione esta linha
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
    // ...
];
```

## 4. Estrutura do Banco de Dados (Migrations)

Crie os seguintes arquivos de migration na pasta `database/migrations/`.

#### 4.1. `YYYY_MM_DD_HHMMSS_create_users_table.php`

Se você não estiver usando o Laravel Jetstream ou Breeze, o Laravel já vem com uma migration de usuários. Certifique-se de que ela existe. Se precisar criar ou modificar:

```php
// database/migrations/xxxx_xx_xx_xxxxxx_create_users_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

#### 4.2. `YYYY_MM_DD_HHMMSS_create_rooms_table.php`

Crie este arquivo com o comando `php artisan make:migration create_rooms_table` e cole o conteúdo:

```php
// Conteúdo de /home/ubuntu/create_rooms_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_private')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
```

#### 4.3. `YYYY_MM_DD_HHMMSS_create_messages_table.php`

Crie com `php artisan make:migration create_messages_table` e cole o conteúdo:

```php
// Conteúdo de /home/ubuntu/create_messages_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            
            $table->index(['room_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
```

#### 4.4. `YYYY_MM_DD_HHMMSS_create_room_user_table.php`

Crie com `php artisan make:migration create_room_user_table` e cole o conteúdo:

```php
// Conteúdo de /home/ubuntu/create_room_user_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();
            
            $table->unique(['room_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_user');
    }
};
```

#### 4.5. Executar as Migrations

Após criar todos os arquivos de migration, execute:

```bash
php artisan migrate
```

Se você já tiver a tabela `personal_access_tokens` de uma instalação anterior do Sanctum, pode ignorar erros relacionados a ela ou remover a migration do Sanctum se estiver recriando.




## 5. Modelos (Models)

Crie os seguintes arquivos na pasta `app/Models/`:

#### 5.1. `Room.php`

```php
// Conteúdo de /home/ubuntu/Room.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_private',
        'created_by',
    ];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessages(): HasMany
    {
        return $this->hasMany(Message::class)
            ->with('user')
            ->latest()
            ->limit(50);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
```

#### 5.2. `Message.php`

```php
// Conteúdo de /home/ubuntu/Message.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'user_id',
        'room_id',
        'edited_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
    ];

    protected $with = ['user'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function isEdited(): bool
    {
        return !is_null($this->edited_at);
    }
}
```

#### 5.3. `User.php` (Modificação)

Abra o arquivo `app/Models/User.php` e adicione os `use` statements e os métodos abaixo. Certifique-se de que o `User` model use o trait `HasApiTokens` do Sanctum.

```php
// Conteúdo de /home/ubuntu/User_Sanctum_Extension.php e /home/ubuntu/User_relationships.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Adicione esta linha
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Adicione esta linha
use Illuminate\Database\Eloquent\Relations\HasMany; // Adicione esta linha

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; // Certifique-se de que HasApiTokens está aqui

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

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
}
```

## 6. Controllers

Crie os seguintes arquivos de controller nas respectivas pastas:

#### 6.1. Controllers para o Frontend (Inertia)

Crie na pasta `app/Http/Controllers/`:

##### 6.1.1. `RoomController.php`

```php
// Conteúdo de /home/ubuntu/RoomController.php
<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(): Response
    {
        $rooms = auth()->user()->rooms()
            ->with(['creator', 'latestMessages'])
            ->get();

        return Inertia::render('Chat/Index', [
            'rooms' => $rooms,
        ]);
    }

    public function show(Room $room): Response
    {
        // Verifica se o usuário tem acesso à sala
        if (!$room->users()->where('user_id', auth()->id())->exists()) {
            abort(403, 'Você não tem acesso a esta sala.');
        }

        $messages = $room->messages()
            ->with('user')
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        return Inertia::render('Chat/Room', [
            'room' => $room->load('users'),
            'messages' => $messages,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_private' => 'boolean',
        ]);

        $room = Room::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(6),
            'description' => $validated['description'],
            'is_private' => $validated['is_private'] ?? false,
            'created_by' => auth()->id(),
        ]);

        // Adiciona o criador à sala
        $room->users()->attach(auth()->id());

        return redirect()->route('rooms.show', $room);
    }

    public function join(Room $room)
    {
        if ($room->is_private) {
            abort(403, 'Esta sala é privada.');
        }

        if (!$room->users()->where('user_id', auth()->id())->exists()) {
            $room->users()->attach(auth()->id());
        }

        return redirect()->route('rooms.show', $room);
    }

    public function leave(Room $room)
    {
        $room->users()->detach(auth()->id());

        return redirect()->route('rooms.index');
    }
}
```

##### 6.1.2. `MessageController.php`

```php
// Conteúdo de /home/ubuntu/MessageController.php
<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\Room;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function store(Request $request, Room $room)
    {
        // Verifica se o usuário tem acesso à sala
        if (!$room->users()->where('user_id', auth()->id())->exists()) {
            abort(403, 'Você não tem acesso a esta sala.');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $message = Message::create([
            'content' => $validated['content'],
            'user_id' => auth()->id(),
            'room_id' => $room->id,
        ]);

        $message->load('user');

        // Dispara o evento de broadcasting
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => $message,
        ]);
    }

    public function update(Request $request, Message $message)
    {
        // Verifica se o usuário é o autor da mensagem
        if ($message->user_id !== auth()->id()) {
            abort(403, 'Você só pode editar suas próprias mensagens.');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $message->update([
            'content' => $validated['content'],
            'edited_at' => now(),
        ]);

        return response()->json([
            'message' => $message->fresh('user'),
        ]);
    }

    public function destroy(Message $message)
    {
        // Verifica se o usuário é o autor da mensagem
        if ($message->user_id !== auth()->id()) {
            abort(403, 'Você só pode deletar suas próprias mensagens.');
        }

        $message->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}
```

#### 6.2. Controllers para a API REST

Crie a pasta `app/Http/Controllers/Api/` e coloque os seguintes arquivos:

##### 6.2.1. `AuthController.php`

```php
// Conteúdo de /home/ubuntu/Api_AuthController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login e criação de token
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        // Revoga tokens existentes do mesmo dispositivo (opcional)
        $user->tokens()->where('name', $request->device_name)->delete();

        $token = $user->createToken($request->device_name, ['chat:read', 'chat:write']);

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
            'abilities' => $token->accessToken->abilities,
        ]);
    }

    /**
     * Registro de novo usuário
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'device_name' => 'required|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken($request->device_name, ['chat:read', 'chat:write']);

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
            'abilities' => $token->accessToken->abilities,
        ], 201);
    }

    /**
     * Logout (revoga token atual)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Token revogado com sucesso.',
        ]);
    }

    /**
     * Logout de todos os dispositivos
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Todos os tokens foram revogados.',
        ]);
    }

    /**
     * Informações do usuário autenticado
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
            'abilities' => $request->user()->currentAccessToken()->abilities,
        ]);
    }

    /**
     * Renovar token
     */
    public function refresh(Request $request)
    {
        $request->validate([
            'device_name' => 'required|string',
        ]);

        $user = $request->user();
        $currentToken = $user->currentAccessToken();
        
        // Revoga o token atual
        $currentToken->delete();
        
        // Cria um novo token
        $newToken = $user->createToken($request->device_name, ['chat:read', 'chat:write']);

        return response()->json([
            'user' => $user,
            'token' => $newToken->plainTextToken,
            'abilities' => $newToken->accessToken->abilities,
        ]);
    }
}
```

##### 6.2.2. `RoomApiController.php`

```php
// Conteúdo de /home/ubuntu/Api_RoomController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomApiController extends Controller
{
    /**
     * Lista todas as salas públicas ou salas do usuário
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Room::with(['creator:id,name', 'users:id,name'])
            ->withCount('users', 'messages');

        // Se o usuário está autenticado, mostra suas salas + públicas
        if ($user) {
            $query->where(function ($q) use ($user) {
                $q->where('is_private', false)
                  ->orWhereHas('users', function ($userQuery) use ($user) {
                      $userQuery->where('user_id', $user->id);
                  });
            });
        } else {
            // Apenas salas públicas para usuários não autenticados
            $query->where('is_private', false);
        }

        $rooms = $query->latest()->paginate(20);

        return response()->json([
            'data' => $rooms->items(),
            'meta' => [
                'current_page' => $rooms->currentPage(),
                'last_page' => $rooms->lastPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
            ]
        ]);
    }

    /**
     * Exibe uma sala específica
     */
    public function show(Request $request, Room $room)
    {
        $user = $request->user();

        // Verifica acesso à sala
        if ($room->is_private && (!$user || !$room->users()->where('user_id', $user->id)->exists())) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você não tem permissão para acessar esta sala.'
            ], 403);
        }

        $room->load(['creator:id,name', 'users:id,name']);
        $room->loadCount('users', 'messages');

        return response()->json([
            'data' => $room
        ]);
    }

    /**
     * Cria uma nova sala
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_private' => 'boolean',
        ]);

        $room = Room::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(6),
            'description' => $request->description,
            'is_private' => $request->boolean('is_private'),
            'created_by' => $request->user()->id,
        ]);

        // Adiciona o criador à sala
        $room->users()->attach($request->user()->id);

        $room->load(['creator:id,name', 'users:id,name']);

        return response()->json([
            'data' => $room,
            'message' => 'Sala criada com sucesso.'
        ], 201);
    }

    /**
     * Atualiza uma sala (apenas o criador)
     */
    public function update(Request $request, Room $room)
    {
        if ($room->created_by !== $request->user()->id) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Apenas o criador pode editar esta sala.'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_private' => 'sometimes|boolean',
        ]);

        $room->update($request->only(['name', 'description', 'is_private']));

        $room->load(['creator:id,name', 'users:id,name']);

        return response()->json([
            'data' => $room,
            'message' => 'Sala atualizada com sucesso.'
        ]);
    }

    /**
     * Remove uma sala (apenas o criador)
     */
    public function destroy(Request $request, Room $room)
    {
        if ($room->created_by !== $request->user()->id) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Apenas o criador pode deletar esta sala.'
            ], 403);
        }

        $room->delete();

        return response()->json([
            'message' => 'Sala deletada com sucesso.'
        ]);
    }

    /**
     * Entrar em uma sala
     */
    public function join(Request $request, Room $room)
    {
        $user = $request->user();

        if ($room->is_private) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Esta sala é privada.'
            ], 403);
        }

        if (!$room->users()->where('user_id', $user->id)->exists()) {
            $room->users()->attach($user->id);
        }

        return response()->json([
            'message' => 'Você entrou na sala com sucesso.',
            'data' => [
                'room_id' => $room->id,
                'user_id' => $user->id,
                'joined_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Sair de uma sala
     */
    public function leave(Request $request, Room $room)
    {
        $user = $request->user();
        
        $room->users()->detach($user->id);

        return response()->json([
            'message' => 'Você saiu da sala com sucesso.'
        ]);
    }

    /**
     * Lista membros de uma sala
     */
    public function members(Request $request, Room $room)
    {
        $user = $request->user();

        // Verifica acesso à sala
        if ($room->is_private && (!$user || !$room->users()->where('user_id', $user->id)->exists())) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você não tem permissão para ver os membros desta sala.'
            ], 403);
        }

        $members = $room->users()
            ->select('users.id', 'users.name', 'room_user.joined_at')
            ->paginate(50);

        return response()->json([
            'data' => $members->items(),
            'meta' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ]
        ]);
    }
}
```

##### 6.2.3. `MessageApiController.php`

```php
// Conteúdo de /home/ubuntu/Api_MessageController.php
<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Room;
use Illuminate\Http\Request;

class MessageApiController extends Controller
{
    /**
     * Lista mensagens de uma sala
     */
    public function index(Request $request, Room $room)
    {
        $user = $request->user();

        // Verifica acesso à sala
        if ($room->is_private && (!$user || !$room->users()->where('user_id', $user->id)->exists())) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você não tem permissão para ver as mensagens desta sala.'
            ], 403);
        }

        $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'before' => 'sometimes|integer|exists:messages,id',
            'after' => 'sometimes|integer|exists:messages,id',
        ]);

        $query = $room->messages()
            ->with('user:id,name')
            ->latest();

        // Paginação baseada em cursor para melhor performance
        if ($request->has('before')) {
            $query->where('id', '<', $request->before);
        }

        if ($request->has('after')) {
            $query->where('id', '>', $request->after);
        }

        $perPage = $request->get('per_page', 50);
        $messages = $query->limit($perPage)->get();

        // Se não há filtro 'after', inverte a ordem para mostrar mais recentes primeiro
        if (!$request->has('after')) {
            $messages = $messages->reverse()->values();
        }

        return response()->json([
            'data' => $messages,
            'meta' => [
                'room_id' => $room->id,
                'count' => $messages->count(),
                'per_page' => $perPage,
                'has_more' => $messages->count() === $perPage,
            ]
        ]);
    }

    /**
     * Exibe uma mensagem específica
     */
    public function show(Request $request, Message $message)
    {
        $user = $request->user();
        $room = $message->room;

        // Verifica acesso à sala
        if ($room->is_private && (!$user || !$room->users()->where('user_id', $user->id)->exists())) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você não tem permissão para ver esta mensagem.'
            ], 403);
        }

        $message->load('user:id,name', 'room:id,name');

        return response()->json([
            'data' => $message
        ]);
    }

    /**
     * Envia uma nova mensagem
     */
    public function store(Request $request, Room $room)
    {
        $user = $request->user();

        // Verifica acesso à sala
        if (!$room->users()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você precisa estar na sala para enviar mensagens.'
            ], 403);
        }

        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $message = Message::create([
            'content' => $request->content,
            'user_id' => $user->id,
            'room_id' => $room->id,
        ]);

        $message->load('user:id,name');

        // Dispara o evento de broadcasting
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'data' => $message,
            'message' => 'Mensagem enviada com sucesso.'
        ], 201);
    }

    /**
     * Atualiza uma mensagem (apenas o autor)
     */
    public function update(Request $request, Message $message)
    {
        if ($message->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você só pode editar suas próprias mensagens.'
            ], 403);
        }

        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $message->update([
            'content' => $request->content,
            'edited_at' => now(),
        ]);

        $message->load('user:id,name');

        return response()->json([
            'data' => $message,
            'message' => 'Mensagem atualizada com sucesso.'
        ]);
    }

    /**
     * Remove uma mensagem (apenas o autor)
     */
    public function destroy(Request $request, Message $message)
    {
        if ($message->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você só pode deletar suas próprias mensagens.'
            ], 403);
        }

        $message->delete();

        return response()->json([
            'message' => 'Mensagem deletada com sucesso.'
        ]);
    }

    /**
     * Busca mensagens por conteúdo
     */
    public function search(Request $request, Room $room)
    {
        $user = $request->user();

        // Verifica acesso à sala
        if ($room->is_private && (!$user || !$room->users()->where('user_id', $user->id)->exists())) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você não tem permissão para buscar nesta sala.'
            ], 403);
        }

        $request->validate([
            'q' => 'required|string|min:3|max:100',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $query = $room->messages()
            ->with('user:id,name')
            ->where('content', 'LIKE', '%' . $request->q . '%')
            ->latest();

        $perPage = $request->get('per_page', 20);
        $messages = $query->paginate($perPage);

        return response()->json([
            'data' => $messages->items(),
            'meta' => [
                'query' => $request->q,
                'room_id' => $room->id,
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ]
        ]);
    }
}
```

##### 6.2.4. `WebSocketAuthController.php`

```php
// Conteúdo de /home/ubuntu/Api_WebSocketAuthController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebSocketAuthController extends Controller
{
    /**
     * Autentica usuário para canais privados do WebSocket
     */
    public function authenticate(Request $request)
    {
        $request->validate([
            'socket_id' => 'required|string',
            'channel_name' => 'required|string',
        ]);

        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Não autenticado',
                'message' => 'Token de autenticação inválido ou expirado.'
            ], 401);
        }

        $channelName = $request->channel_name;
        $socketId = $request->socket_id;

        // Verifica se é um canal privado de sala
        if (preg_match('/^private-room\.(\d+)$/', $channelName, $matches)) {
            $roomId = $matches[1];
            $room = Room::find($roomId);

            if (!$room) {
                return response()->json([
                    'error' => 'Sala não encontrada',
                    'message' => 'A sala especificada não existe.'
                ], 404);
            }

            // Verifica se o usuário tem acesso à sala
            if ($room->is_private && !$room->users()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'error' => 'Acesso negado',
                    'message' => 'Você não tem permissão para acessar esta sala.'
                ], 403);
            }

            // Gera a assinatura de autenticação do Pusher
            $pusher = app('pusher');
            $auth = $pusher->socket_auth($channelName, $socketId);

            return response()->json([
                'auth' => $auth,
                'channel_data' => json_encode([
                    'user_id' => $user->id,
                    'user_info' => [
                        'id' => $user->id,
                        'name' => $user->name,
                    ]
                ])
            ]);
        }

        // Verifica se é um canal de presença
        if (preg_match('/^presence-room\.(\d+)$/', $channelName, $matches)) {
            $roomId = $matches[1];
            $room = Room::find($roomId);

            if (!$room) {
                return response()->json([
                    'error' => 'Sala não encontrada',
                    'message' => 'A sala especificada não existe.'
                ], 404);
            }

            // Verifica acesso à sala
            if ($room->is_private && !$room->users()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'error' => 'Acesso negado',
                    'message' => 'Você não tem permissão para acessar esta sala.'
                ], 403);
            }

            $pusher = app('pusher');
            $presence_data = [
                'user_id' => $user->id,
                'user_info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ]
            ];

            $auth = $pusher->presence_auth($channelName, $socketId, $user->id, $presence_data);

            return response()->json([
                'auth' => $auth,
                'channel_data' => json_encode($presence_data)
            ]);
        }

        return response()->json([
            'error' => 'Canal inválido',
            'message' => 'O canal especificado não é válido.'
        ], 400);
    }

    /**
     * Lista canais disponíveis para o usuário
     */
    public function channels(Request $request)
    {
        $user = $request->user();
        
        $rooms = $user->rooms()->select('id', 'name', 'is_private')->get();
        
        $channels = $rooms->map(function ($room) {
            return [
                'room_id' => $room->id,
                'room_name' => $room->name,
                'is_private' => $room->is_private,
                'channels' => [
                    'private' => "private-room.{$room->id}",
                    'presence' => "presence-room.{$room->id}",
                    'public' => $room->is_private ? null : "public.room.{$room->id}",
                ]
            ];
        });

        return response()->json([
            'data' => $channels,
            'websocket_config' => [
                'host' => config('broadcasting.connections.pusher.options.host'),
                'port' => config('broadcasting.connections.pusher.options.port'),
                'key' => config('broadcasting.connections.pusher.key'),
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
            ]
        ]);
    }

    /**
     * Testa conexão WebSocket
     */
    public function test(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Conexão WebSocket disponível',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }
}
```

## 7. Eventos (Events)

Crie a pasta `app/Events/` e coloque o seguinte arquivo:

#### 7.1. `MessageSent.php`

```php
// Conteúdo de /home/ubuntu/MessageSent.php
<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('room.' . $this->message->room_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'content' => $this->message->content,
            'user' => [
                'id' => $this->message->user->id,
                'name' => $this->message->user->name,
            ],
            'room_id' => $this->message->room_id,
            'created_at' => $this->message->created_at->toISOString(),
            'edited_at' => $this->message->edited_at?->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
```

#### 7.2. `MessageSentApi.php` (para clientes externos)

```php
// Conteúdo de /home/ubuntu/MessageSentApi.php
<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSentApi implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('room.' . $this->message->room_id),
            new Channel('public.room.' . $this->message->room_id), // Canal público para clientes externos
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'content' => $this->message->content,
            'user' => [
                'id' => $this->message->user->id,
                'name' => $this->message->user->name,
            ],
            'room_id' => $this->message->room_id,
            'created_at' => $this->message->created_at->toISOString(),
            'edited_at' => $this->message->edited_at?->toISOString(),
            'is_edited' => !is_null($this->message->edited_at),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Determina se o evento deve ser transmitido
     */
    public function shouldBroadcast(): bool
    {
        // Só transmite se a sala não for privada ou se for um canal privado
        return !$this->message->room->is_private || $this->socket !== null;
    }
}
```

## 8. Configurações Adicionais do Backend

### 8.1. `config/broadcasting.php`

Substitua o conteúdo do arquivo `config/broadcasting.php` pelo seguinte:

```php
// Conteúdo de /home/ubuntu/broadcasting.php
<?php

return [

    'default' => env('BROADCAST_DRIVER', 'null'),

    'connections' => [

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'host' => env('PUSHER_HOST') ?: 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusherapp.com',
                'port' => env('PUSHER_PORT', 443),
                'scheme' => env('PUSHER_SCHEME', 'https'),
                'encrypted' => true,
                'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
            ],
            'client_options' => [
                // Guzzle client options: https://docs.guzzlephp.org/en/stable/request-options.html
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
```

### 8.2. `config/cors.php`

Substitua o conteúdo do arquivo `config/cors.php` pelo seguinte:

```php
// Conteúdo de /home/ubuntu/cors.php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Adicione seus domínios aqui, ou use a variável de ambiente
        // explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:8080'))
        'http://localhost:3000',
        'http://localhost:8080',
        'https://meusite.com',
        'https://app.meusite.com',
        // Para desenvolvimento, você pode usar '*' mas NÃO em produção
        // '*'
    ],

    'allowed_origins_patterns' => [
        // Permite subdomínios específicos
        '/^https:\/\/.*\.meusite\.com$/',
        '/^http:\/\/localhost:\d+$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
```

### 8.3. `config/sanctum.php`

Substitua o conteúdo do arquivo `config/sanctum.php` pelo seguinte:

```php
// Conteúdo de /home/ubuntu/sanctum.php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be checked when
    | Sanctum is trying to authenticate a request. If none of these guards
    | are able to authenticate the request, Sanctum will use the bearer
    | token that's present on an incoming request for authentication.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. If this value is null, personal access tokens do
    | not expire. This won't tweak the lifetime of first-party sessions.
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When authenticating your first-party SPA with Sanctum you may need to
    | customize some of the middleware Sanctum uses while processing the
    | request. You may change the middleware listed below as required.
    |
    */

    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],

];
```

### 8.4. `app/Http/Middleware/ApiSecurityMiddleware.php`

Crie este arquivo:

```php
// Conteúdo de /home/ubuntu/ApiSecurityMiddleware.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiSecurityMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se a requisição vem de um domínio autorizado
        $allowedOrigins = config('cors.allowed_origins', []);
        $origin = $request->header('Origin');
        
        if ($origin && !in_array($origin, $allowedOrigins) && !$this->matchesPattern($origin)) {
            return response()->json([
                'error' => 'Origem não autorizada',
                'message' => 'Este domínio não tem permissão para acessar a API.'
            ], 403);
        }

        // Adiciona headers de segurança
        $response = $next($request);
        
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        return $response;
    }

    /**
     * Verifica se a origem corresponde aos padrões permitidos
     */
    private function matchesPattern(string $origin): bool
    {
        $patterns = config('cors.allowed_origins_patterns', []);
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $origin)) {
                return true;
            }
        }
        
        return false;
    }
}
```

### 8.5. Registrar Middleware

Abra `app/Http/Kernel.php` e adicione o `ApiSecurityMiddleware` ao grupo `api`:

```php
// Dentro de app/Http/Kernel.php

protected $middlewareGroups = [
    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \App\Http\Middleware\ApiSecurityMiddleware::class, // Adicione esta linha
    ],
];
```

### 8.6. Habilitar Broadcast Service Provider

No arquivo `config/app.php`, descomente a linha do `BroadcastServiceProvider`:

```php
// Dentro de config/app.php

'providers' => [
    // ...
    App\Providers\BroadcastServiceProvider::class,
    // ...
],
```

## 9. Rotas

### 9.1. `routes/web.php` (para o Frontend Inertia)

Adicione as rotas do chat ao seu arquivo `routes/web.php`. Se você estiver usando Jetstream, adicione-as dentro do grupo de middleware `auth` e `verified`.

```php
// Conteúdo de /home/ubuntu/web.php
<?php

use App\Http\Controllers\MessageController;
use App\Http\Controllers\RoomController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Chat Routes
    Route::prefix('chat')->group(function () {
        Route::get('/', [RoomController::class, 'index'])->name('rooms.index');
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
        Route::post('/rooms/{room}/join', [RoomController::class, 'join'])->name('rooms.join');
        Route::delete('/rooms/{room}/leave', [RoomController::class, 'leave'])->name('rooms.leave');
        
        Route::post('/rooms/{room}/messages', [MessageController::class, 'store'])->name('messages.store');
        Route::put('/messages/{message}', [MessageController::class, 'update'])->name('messages.update');
        Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');
    });
});
```

### 9.2. `routes/api.php` (para a API REST)

Substitua o conteúdo do arquivo `routes/api.php` pelo seguinte:

```php
// Conteúdo de /home/ubuntu/api.php
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MessageApiController;
use App\Http\Controllers\Api\RoomApiController;
use App\Http\Controllers\Api\WebSocketAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rotas públicas (sem autenticação)
Route::prefix('v1')->group(function () {
    
    // Autenticação
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    
    // Salas públicas (apenas listagem e visualização)
    Route::get('/rooms', [RoomApiController::class, 'index']);
    Route::get('/rooms/{room}', [RoomApiController::class, 'show']);
    Route::get('/rooms/{room}/members', [RoomApiController::class, 'members']);
    Route::get('/rooms/{room}/messages', [MessageApiController::class, 'index']);
    Route::get('/rooms/{room}/messages/search', [MessageApiController::class, 'search']);
    Route::get('/messages/{message}', [MessageApiController::class, 'show']);
});

// Rotas protegidas (requer autenticação)
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    
    // Autenticação
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    
    // Salas - CRUD completo
    Route::apiResource('rooms', RoomApiController::class);
    Route::post('/rooms/{room}/join', [RoomApiController::class, 'join']);
    Route::delete('/rooms/{room}/leave', [RoomApiController::class, 'leave']);
    Route::get('/rooms/{room}/members', [RoomApiController::class, 'members']);
    
    // Mensagens - CRUD completo
    Route::apiResource('rooms.messages', MessageApiController::class, [
        'except' => ['index', 'show'] // Já definidos nas rotas públicas
    ]);
    Route::get('/rooms/{room}/messages/search', [MessageApiController::class, 'search']);
    
    // Rotas diretas para mensagens (sem precisar da sala)
    Route::put('/messages/{message}', [MessageApiController::class, 'update']);
    Route::delete('/messages/{message}', [MessageApiController::class, 'destroy']);
});

// Rota para autenticação do broadcasting (WebSocket)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/broadcasting/auth', function (Request $request) {
        return response()->json([
            'auth' => auth()->user() ? auth()->user()->id : null,
        ]);
    });
});

// Rotas de autenticação WebSocket para clientes externos
Route::prefix('v1')->group(function () {
    Route::post('/websocket/auth', [WebSocketAuthController::class, 'authenticate']);
    Route::get('/websocket/channels', [WebSocketAuthController::class, 'channels']);
    Route::get('/websocket/test', [WebSocketAuthController::class, 'test']);
});

// Rota de status da API
Route::get('/v1/status', function () {
    return response()->json([
        'status' => 'online',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
        'endpoints' => [
            'auth' => '/api/v1/auth/*',
            'rooms' => '/api/v1/rooms',
            'messages' => '/api/v1/rooms/{room}/messages',
            'websocket' => config('broadcasting.connections.pusher.options.host'),
        ]
    ]);
});

// Fallback para rotas não encontradas
Route::fallback(function () {
    return response()->json([
        'error' => 'Endpoint não encontrado',
        'message' => 'A rota solicitada não existe. Consulte a documentação da API.',
        'documentation' => '/api/v1/status'
    ], 404);
});
```

### 9.3. `routes/channels.php` (Canais de Broadcasting)

Substitua o conteúdo do arquivo `routes/channels.php` pelo seguinte:

```php
// Conteúdo de /home/ubuntu/channels_api.php
<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Canal de usuário individual
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal privado de sala (para usuários autenticados via web)
Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    // Verifica se o usuário tem acesso à sala
    return $user->rooms()->where('room_id', $roomId)->exists();
});

// Canal privado de sala (para clientes externos via API)
Broadcast::channel('private-room.{roomId}', function ($user, $roomId) {
    $room = \App\Models\Room::find($roomId);
    
    if (!$room) {
        return false;
    }
    
    // Se a sala é privada, verifica se o usuário é membro
    if ($room->is_private) {
        return $room->users()->where('user_id', $user->id)->exists();
    }
    
    // Salas públicas permitem acesso a qualquer usuário autenticado
    return true;
});

// Canal de presença para mostrar usuários online na sala
Broadcast::channel('presence-room.{roomId}', function ($user, $roomId) {
    $room = \App\Models\Room::find($roomId);
    
    if (!$room) {
        return false;
    }
    
    // Verifica acesso à sala
    if ($room->is_private && !$room->users()->where('user_id', $user->id)->exists()) {
        return false;
    }
    
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});

// Canais públicos não precisam de autorização (definidos no evento)
```

## 10. Configuração do Frontend (Vue.js com Inertia)

### 10.1. Instalar Dependências JavaScript

Substitua o conteúdo do seu `package.json` pelo seguinte e instale as dependências:

```json
// Conteúdo de /home/ubuntu/package.json
{
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite dev"
    },
    "devDependencies": {
        "@inertiajs/vue3": "^1.0.0",
        "@tailwindcss/forms": "^0.5.3",
        "@tailwindcss/typography": "^0.5.0",
        "@vitejs/plugin-vue": "^4.0.0",
        "autoprefixer": "^10.4.12",
        "axios": "^1.1.2",
        "laravel-vite-plugin": "^0.7.2",
        "postcss": "^8.4.31",
        "tailwindcss": "^3.2.1",
        "vite": "^4.0.0"
    },
    "dependencies": {
        "laravel-echo": "^1.15.0",
        "pusher-js": "^8.0.1",
        "vue": "^3.2.31"
    }
}
```

```bash
npm install
```

### 10.2. Arquivos JavaScript

Crie ou substitua os seguintes arquivos na pasta `resources/js/`:

#### 10.2.1. `resources/js/echo.js`

```javascript
// Conteúdo de /home/ubuntu/echo.js
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusherapp.com`,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
    },
})

export default window.Echo
```

#### 10.2.2. `resources/js/app.js`

Substitua o conteúdo do seu `resources/js/app.js` pelo seguinte:

```javascript
// Conteúdo de /home/ubuntu/app.js
import './bootstrap'
import './echo'

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel'

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue'))),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el)
    },
    progress: {
        color: '#4B5563',
    },
})
```

### 10.3. Componentes Vue.js

Crie a pasta `resources/js/Pages/Chat/` e coloque os seguintes arquivos:

#### 10.3.1. `resources/js/Pages/Chat/Index.vue`

```vue
<!-- Conteúdo de /home/ubuntu/Chat_Index.vue -->
<template>
  <AppLayout title="Chat">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Chat
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6">
            <!-- Header com botão para criar sala -->
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-medium text-gray-900">Suas Salas de Chat</h3>
              <button
                @click="showCreateModal = true"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
              >
                Nova Sala
              </button>
            </div>

            <!-- Lista de salas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="room in rooms"
                :key="room.id"
                class="border rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                @click="$inertia.visit(route('rooms.show', room.slug))"
              >
                <h4 class="font-semibold text-gray-900 mb-2">{{ room.name }}</h4>
                <p class="text-gray-600 text-sm mb-3">{{ room.description || 'Sem descrição' }}</p>
                
                <div class="flex items-center justify-between text-xs text-gray-500">
                  <span>{{ room.users_count || 0 }} membros</span>
                  <span v-if="room.latest_messages?.length">
                    Última mensagem: {{ formatDate(room.latest_messages[0].created_at) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Estado vazio -->
            <div v-if="rooms.length === 0" class="text-center py-12">
              <div class="text-gray-500 mb-4">
                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.001 8.001 0 01-7.003-4.165L2 20l4.165-4.003A8.001 8.001 0 0112 4c4.418 0 8 3.582 8 8z" />
                </svg>
              </div>
              <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma sala encontrada</h3>
              <p class="text-gray-500 mb-4">Crie sua primeira sala de chat para começar.</p>
              <button
                @click="showCreateModal = true"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
              >
                Criar Primeira Sala
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para criar sala -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Criar Nova Sala</h3>
          
          <form @submit.prevent="createRoom">
            <div class="mb-4">
              <label class="block text-gray-700 text-sm font-bold mb-2">
                Nome da Sala
              </label>
              <input
                v-model="form.name"
                type="text"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                required
              >
            </div>
            
            <div class="mb-4">
              <label class="block text-gray-700 text-sm font-bold mb-2">
                Descrição (opcional)
              </label>
              <textarea
                v-model="form.description"
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                rows="3"
              ></textarea>
            </div>
            
            <div class="mb-6">
              <label class="flex items-center">
                <input
                  v-model="form.is_private"
                  type="checkbox"
                  class="form-checkbox h-4 w-4 text-blue-600"
                >
                <span class="ml-2 text-gray-700">Sala privada</span>
              </label>
            </div>
            
            <div class="flex justify-end space-x-3">
              <button
                type="button"
                @click="showCreateModal = false"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="processing"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
              >
                {{ processing ? 'Criando...' : 'Criar Sala' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  rooms: Array
})

const showCreateModal = ref(false)
const processing = ref(false)

const form = reactive({
  name: '',
  description: '',
  is_private: false
})

const createRoom = () => {
  processing.value = true
  
  router.post(route('rooms.store'), form, {
    onSuccess: () => {
      showCreateModal.value = false
      form.name = ''
      form.description = ''
      form.is_private = false
    },
    onFinish: () => {
      processing.value = false
    }
  })
}

const formatDate = (date) => {
  return new Date(date).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>
```

#### 10.3.2. `resources/js/Pages/Chat/Room.vue`

```vue
<!-- Conteúdo de /home/ubuntu/Chat_Room_WithEcho.vue -->
<template>
  <AppLayout :title="room.name">
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ room.name }}
          </h2>
          <p class="text-sm text-gray-600">{{ room.description }}</p>
        </div>
        <div class="flex items-center space-x-4">
          <span class="text-sm text-gray-500">{{ room.users?.length || 0 }} membros</span>
          <div :class="connectionStatus === 'connected' ? 'bg-green-500' : 'bg-red-500'" class="w-2 h-2 rounded-full"></div>
          <span class="text-xs text-gray-500">{{ connectionStatus === 'connected' ? 'Online' : 'Offline' }}</span>
        </div>
        <button
          @click="$inertia.visit(route('rooms.index'))"
          class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm"
        >
          Voltar
        </button>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg h-[calc(100vh-200px)] flex flex-col">
          
          <!-- Área de mensagens -->
          <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4">
            <div
              v-for="message in messages"
              :key="message.id"
              class="flex items-start space-x-3"
              :class="{ 'animate-pulse': message.sending }"
            >
              <!-- Avatar -->
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                  {{ message.user.name.charAt(0).toUpperCase() }}
                </div>
              </div>
              
              <!-- Conteúdo da mensagem -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2">
                  <span class="font-semibold text-gray-900">{{ message.user.name }}</span>
                  <span class="text-xs text-gray-500">{{ formatDate(message.created_at) }}</span>
                  <span v-if="message.edited_at" class="text-xs text-gray-400">(editada)</span>
                  <span v-if="message.sending" class="text-xs text-blue-500">Enviando...</span>
                </div>
                <p class="text-gray-700 mt-1 whitespace-pre-wrap">{{ message.content }}</p>
                
                <!-- Ações da mensagem (apenas para o autor) -->
                <div v-if="message.user.id === $page.props.auth.user.id && !message.sending" class="mt-2 flex space-x-2">
                  <button
                    @click="editMessage(message)"
                    class="text-xs text-blue-600 hover:text-blue-800"
                  >
                    Editar
                  </button>
                  <button
                    @click="deleteMessage(message)"
                    class="text-xs text-red-600 hover:text-red-800"
                  >
                    Deletar
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Estado vazio -->
            <div v-if="messages.length === 0" class="text-center py-12">
              <div class="text-gray-500 mb-4">
                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.001 8.001 0 01-7.003-4.165L2 20l4.165-4.003A8.001 8.001 0 0112 4c4.418 0 8 3.582 8 8z" />
                </svg>
              </div>
              <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma mensagem ainda</h3>
              <p class="text-gray-500">Seja o primeiro a enviar uma mensagem nesta sala!</p>
            </div>
          </div>
          
          <!-- Formulário de envio -->
          <div class="border-t bg-gray-50 p-4">
            <form @submit.prevent="sendMessage" class="flex space-x-4">
              <div class="flex-1">
                <textarea
                  v-model="newMessage"
                  @keydown.enter.exact.prevent="sendMessage"
                  @keydown.enter.shift.exact="newMessage += '\n'"
                  placeholder="Digite sua mensagem... (Enter para enviar, Shift+Enter para nova linha)"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 resize-none"
                  rows="2"
                  :disabled="sending"
                ></textarea>
              </div>
              <button
                type="submit"
                :disabled="!newMessage.trim() || sending"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ sending ? 'Enviando...' : 'Enviar' }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de edição -->
    <div v-if="editingMessage" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Editar Mensagem</h3>
          
          <form @submit.prevent="updateMessage">
            <div class="mb-4">
              <textarea
                v-model="editForm.content"
                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                rows="4"
                required
              ></textarea>
            </div>
            
            <div class="flex justify-end space-x-3">
              <button
                type="button"
                @click="cancelEdit"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="updating"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
              >
                {{ updating ? 'Salvando...' : 'Salvar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, nextTick, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Echo from '@/echo'

const props = defineProps({
  room: Object,
  messages: Array
})

const messages = ref([...props.messages])
const newMessage = ref('')
const sending = ref(false)
const messagesContainer = ref(null)
const connectionStatus = ref('connecting')

const editingMessage = ref(null)
const updating = ref(false)
const editForm = reactive({
  content: ''
})

let channel = null

const sendMessage = async () => {
  if (!newMessage.value.trim() || sending.value) return
  
  sending.value = true
  
  // Adiciona mensagem temporária com indicador de envio
  const tempMessage = {
    id: Date.now(),
    content: newMessage.value,
    user: {
      id: props.room.users.find(u => u.id === window.Laravel.user.id) || window.Laravel.user,
      name: window.Laravel.user.name
    },
    created_at: new Date().toISOString(),
    sending: true
  }
  
  messages.value.push(tempMessage)
  const messageContent = newMessage.value
  newMessage.value = ''
  
  await nextTick()
  scrollToBottom()
  
  try {
    const response = await fetch(route('messages.store', props.room.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify({
        content: messageContent
      })
    })
    
    if (response.ok) {
      // Remove a mensagem temporária
      const tempIndex = messages.value.findIndex(m => m.id === tempMessage.id)
      if (tempIndex !== -1) {
        messages.value.splice(tempIndex, 1)
      }
      // A mensagem real será adicionada via broadcasting
    } else {
      // Remove mensagem temporária em caso de erro
      const tempIndex = messages.value.findIndex(m => m.id === tempMessage.id)
      if (tempIndex !== -1) {
        messages.value.splice(tempIndex, 1)
      }
      newMessage.value = messageContent // Restaura o conteúdo
      alert('Erro ao enviar mensagem. Tente novamente.')
    }
  } catch (error) {
    console.error('Erro ao enviar mensagem:', error)
    // Remove mensagem temporária em caso de erro
    const tempIndex = messages.value.findIndex(m => m.id === tempMessage.id)
    if (tempIndex !== -1) {
      messages.value.splice(tempIndex, 1)
    }
    newMessage.value = messageContent // Restaura o conteúdo
    alert('Erro ao enviar mensagem. Tente novamente.')
  } finally {
    sending.value = false
  }
}

const editMessage = (message) => {
  editingMessage.value = message
  editForm.content = message.content
}

const cancelEdit = () => {
  editingMessage.value = null
  editForm.content = ''
}

const updateMessage = async () => {
  if (!editForm.content.trim() || updating.value) return
  
  updating.value = true
  
  try {
    const response = await fetch(route('messages.update', editingMessage.value.id), {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify({
        content: editForm.content
      })
    })
    
    if (response.ok) {
      const data = await response.json()
      const index = messages.value.findIndex(m => m.id === editingMessage.value.id)
      if (index !== -1) {
        messages.value[index] = data.message
      }
      cancelEdit()
    }
  } catch (error) {
    console.error('Erro ao editar mensagem:', error)
  } finally {
    updating.value = false
  }
}

const deleteMessage = async (message) => {
  if (!confirm('Tem certeza que deseja deletar esta mensagem?')) return
  
  try {
    const response = await fetch(route('messages.destroy', message.id), {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      }
    })
    
    if (response.ok) {
      const index = messages.value.findIndex(m => m.id === message.id)
      if (index !== -1) {
        messages.value.splice(index, 1)
      }
    }
  } catch (error) {
    console.error('Erro ao deletar mensagem:', error)
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const setupEcho = () => {
  channel = Echo.private(`room.${props.room.id}`)
    .listen('.message.sent', (e) => {
      // Verifica se a mensagem já existe (evita duplicatas)
      const exists = messages.value.find(m => m.id === e.id)
      if (!exists) {
        messages.value.push({
          id: e.id,
          content: e.content,
          user: e.user,
          room_id: e.room_id,
          created_at: e.created_at,
          edited_at: e.edited_at
        })
        
        nextTick(() => {
          scrollToBottom()
        })
      }
    })
  
  // Monitora status da conexão
  Echo.connector.pusher.connection.bind('connected', () => {
    connectionStatus.value = 'connected'
  })
  
  Echo.connector.pusher.connection.bind('disconnected', () => {
    connectionStatus.value = 'disconnected'
  })
  
  Echo.connector.pusher.connection.bind('failed', () => {
    connectionStatus.value = 'failed'
  })
}

onMounted(() => {
  scrollToBottom()
  setupEcho()
})

onUnmounted(() => {
  if (channel) {
    Echo.leave(`room.${props.room.id}`)
  }
})
</script>
```

### 10.4. Layout do Aplicativo (Exemplo)

Se você não estiver usando Jetstream, precisará de um layout básico para o Inertia. Crie `resources/js/Layouts/AppLayout.vue`:

```vue
<template>
  <div>
    <nav class="bg-white border-b border-gray-100">
      <!-- Primary Navigation Menu -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <!-- Logo -->
            <div class="shrink-0 flex items-center">
              <Link :href="route('dashboard')">
                <ApplicationMark class="block h-9 w-auto" />
              </Link>
            </div>

            <!-- Navigation Links -->
            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
              <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                Dashboard
              </NavLink>
              <NavLink :href="route('rooms.index')" :active="route().current('rooms.index')">
                Chat
              </NavLink>
            </div>
          </div>

          <div class="hidden sm:flex sm:items-center sm:ml-6">
            <!-- Settings Dropdown -->
            <div class="ml-3 relative">
              <Dropdown align="right" width="48">
                <template #trigger>
                  <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                    <img class="h-8 w-8 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                  </button>

                  <span v-else class="inline-flex rounded-md">
                    <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                      {{ $page.props.auth.user.name }}

                      <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                      </svg>
                    </button>
                  </span>
                </template>

                <template #content>
                  <!-- Account Management -->
                  <div class="block px-4 py-2 text-xs text-gray-400">
                    Manage Account
                  </div>

                  <DropdownLink :href="route('profile.show')">
                    Profile
                  </DropdownLink>

                  <div class="border-t border-gray-200" />

                  <!-- Authentication -->
                  <form @submit.prevent="logout">
                    <DropdownLink as="button">
                      Log Out
                    </DropdownLink>
                  </form>
                </template>
              </Dropdown>
            </div>
          </div>

          <!-- Hamburger -->
          <div class="-mr-2 flex items-center sm:hidden">
            <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
              <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Responsive Navigation Menu -->
      <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
          <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
            Dashboard
          </ResponsiveNavLink>
          <ResponsiveNavLink :href="route('rooms.index')" :active="route().current('rooms.index')">
            Chat
          </ResponsiveNavLink>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
          <div class="flex items-center px-4">
            <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 mr-3">
              <img class="h-10 w-10 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
            </div>

            <div>
              <div class="font-medium text-base text-gray-800">
                {{ $page.props.auth.user.name }}
              </div>
              <div class="font-medium text-sm text-gray-500">
                {{ $page.props.auth.user.email }}
              </div>
            </div>
          </div>

          <div class="mt-3 space-y-1">
            <ResponsiveNavLink :href="route('profile.show')" :active="route().current('profile.show')">
              Profile
            </ResponsiveNavLink>

            <!-- Authentication -->
            <form method="POST" @submit.prevent="logout">
              <ResponsiveNavLink as="button">
                Log Out
              </ResponsiveNavLink>
            </form>
          </div>
        </div>
      </div>
    </nav>

    <!-- Page Heading -->
    <header v-if="$slots.header" class="bg-white shadow">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <slot name="header" />
      </div>
    </header>

    <!-- Page Content -->
    <main>
      <slot />
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

defineProps({
  title: String,
});

const showingNavigationDropdown = ref(false);

const logout = () => {
  router.post(route('logout'));
};
</script>
```

**Nota:** Este `AppLayout.vue` é um exemplo simplificado baseado no Jetstream. Se você não estiver usando Jetstream, precisará adaptar os componentes `ApplicationMark`, `Dropdown`, `DropdownLink`, `NavLink`, `ResponsiveNavLink` ou criar os seus próprios.

## 11. Compilar Assets do Frontend

Após configurar todos os arquivos JavaScript e Vue.js, compile os assets:

```bash
npm run dev
# Para produção:
npm run build
```

## 12. Executar o Sistema

### 12.1. Iniciar o Servidor Laravel

```bash
php artisan serve
```

### 12.2. Iniciar o Queue Worker (Essencial para Broadcasting)

O Laravel usa queues para disparar eventos de broadcasting. Abra um novo terminal e execute:

```bash
php artisan queue:work
```

**Importante:** Este comando deve estar sempre rodando para que as mensagens em tempo real funcionem. Em produção, você usaria um gerenciador de processos como Supervisor para mantê-lo ativo.

### 12.3. Iniciar o Servidor de Desenvolvimento Frontend

Abra outro terminal e execute:

```bash
npm run dev
```

## 13. Testando o Sistema

### 13.1. Frontend (Inertia)

1. Acesse `http://localhost:8000` (ou o URL configurado no seu `.env`).
2. Registre um novo usuário ou faça login.
3. Navegue para a rota `/chat`.
4. Crie uma nova sala de chat.
5. Abra a mesma sala em duas abas diferentes do navegador (ou com outro usuário).
6. Envie mensagens e observe a sincronização em tempo real.

### 13.2. API REST

Você pode usar ferramentas como Postman, Insomnia ou `curl` para testar a API.

#### 13.2.1. Verificar Status da API

```bash
curl http://localhost:8000/api/v1/status
```

Você deve receber uma resposta JSON com o status `online`.

#### 13.2.2. Registrar um Usuário via API

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Api User",
    "email": "apiuser@example.com",
    "password": "password",
    "password_confirmation": "password",
    "device_name": "Postman"
  }'
```

Copie o `token` da resposta. Ele será usado para as próximas requisições autenticadas.

#### 13.2.3. Criar uma Sala via API

```bash
curl -X POST http://localhost:8000/api/v1/rooms \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -d '{
    "name": "Sala da API",
    "description": "Sala criada via API REST",
    "is_private": false
  }'
```

#### 13.2.4. Enviar Mensagem via API

```bash
curl -X POST http://localhost:8000/api/v1/rooms/1/messages \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -d '{
    "content": "Olá do Postman!"
  }'
```

(Substitua `1` pelo ID da sala que você criou).

### 13.3. Testando o WebSocket (Clientes Externos)

Você pode usar um cliente WebSocket simples ou o exemplo em JavaScript/React fornecido na documentação da API para testar a conexão e o recebimento de mensagens em tempo real.

#### Exemplo de Configuração de Cliente JavaScript (fora do Laravel)

```javascript
// Certifique-se de ter instalado: npm install pusher-js
import Pusher from 'pusher-js';

const API_BASE_URL = 'http://localhost:8000/api/v1';
let authToken = 'SEU_TOKEN_AQUI'; // Obtenha este token do login da API

async function setupExternalChatClient() {
    // 1. Obter configurações do WebSocket e canais disponíveis
    const configResponse = await fetch(`${API_BASE_URL}/websocket/channels`, {
        headers: {
            'Authorization': `Bearer ${authToken}`,
            'Accept': 'application/json'
        }
    });
    const configData = await configResponse.json();
    const websocketConfig = configData.websocket_config;
    const userRooms = configData.data; // Salas que o usuário tem acesso

    // 2. Configurar Pusher
    const pusher = new Pusher(websocketConfig.key, {
        cluster: websocketConfig.cluster,
        wsHost: websocketConfig.host,
        wsPort: websocketConfig.port,
        wssPort: websocketConfig.port,
        forceTLS: websocketConfig.scheme === 'https', // Ajuste conforme seu esquema
        enabledTransports: ['ws', 'wss'],
        authEndpoint: `${API_BASE_URL}/websocket/auth`, // Endpoint de autenticação da API
        auth: {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        }
    });

    // 3. Inscrever-se nos canais das salas
    userRooms.forEach(room => {
        // Inscreva-se no canal privado da sala
        const privateChannelName = room.channels.private;
        const privateChannel = pusher.subscribe(privateChannelName);

        privateChannel.bind('message.sent', function(data) {
            console.log(`Nova mensagem na sala ${room.name}:`, data);
            // Atualize sua UI externa aqui
            displayExternalMessage(room.name, data);
        });

        console.log(`Inscrito no canal privado: ${privateChannelName}`);

        // Se a sala não for privada, você também pode se inscrever no canal público
        if (!room.is_private && room.channels.public) {
            const publicChannelName = room.channels.public;
            const publicChannel = pusher.subscribe(publicChannelName);
            publicChannel.bind('message.sent', function(data) {
                console.log(`Nova mensagem (pública) na sala ${room.name}:`, data);
                displayExternalMessage(room.name, data);
            });
            console.log(`Inscrito no canal público: ${publicChannelName}`);
        }
    });

    // Monitorar status da conexão
    pusher.connection.bind('connected', () => {
        console.log('Conectado ao WebSocket!');
    });

    pusher.connection.bind('disconnected', () => {
        console.log('Desconectado do WebSocket.');
    });

    pusher.connection.bind('error', (err) => {
        console.error('Erro no WebSocket:', err);
    });
}

function displayExternalMessage(roomName, message) {
    const messagesDiv = document.getElementById('external-messages');
    if (messagesDiv) {
        const msgElement = document.createElement('p');
        msgElement.textContent = `[${roomName}] ${message.user.name}: ${message.content} (${new Date(message.created_at).toLocaleTimeString()})`;
        messagesDiv.appendChild(msgElement);
        messagesDiv.scrollTop = messagesDiv.scrollHeight; // Scroll para o final
    }
}

// Chame esta função após obter o token de autenticação
// setupExternalChatClient();

// Exemplo de HTML para o cliente externo
/*
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>External Chat Client</title>
</head>
<body>
    <h1>External Chat Client</h1>
    <div id="external-messages" style="height: 300px; border: 1px solid #ccc; overflow-y: scroll; padding: 10px;"></div>
    <script type="module" src="./your-external-script.js"></script>
</body>
</html>
*/
```

## 14. Estrutura de Arquivos Final

Após seguir todos os passos, a estrutura de arquivos do seu projeto deve se assemelhar a:

```
seu-projeto/
├── app/
│   ├── Events/
│   │   ├── MessageSent.php
│   │   └── MessageSentApi.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── MessageApiController.php
│   │   │   │   ├── RoomApiController.php
│   │   │   │   └── WebSocketAuthController.php
│   │   │   ├── MessageController.php
│   │   │   └── RoomController.php
│   │   └── Middleware/
│   │       ├── ApiSecurityMiddleware.php
│   │       └── HandleInertiaRequests.php
│   └── Models/
│       ├── Message.php
│       ├── Room.php
│       └── User.php (modificado)
├── config/
│   ├── app.php (modificado)
│   ├── broadcasting.php (modificado)
│   ├── cors.php (modificado)
│   └── sanctum.php (modificado)
├── database/
│   └── migrations/
│       ├── ... (migrations padrão do Laravel)
│       ├── YYYY_MM_DD_HHMMSS_create_rooms_table.php
│       ├── YYYY_MM_DD_HHMMSS_create_messages_table.php
│       └── YYYY_MM_DD_HHMMSS_create_room_user_table.php
├── public/
├── resources/
│   └── js/
│       ├── Components/ (se usar Jetstream ou componentes próprios)
│       ├── Layouts/
│       │   └── AppLayout.vue
│       ├── Pages/
│       │   ├── Chat/
│       │   │   ├── Index.vue
│       │   │   └── Room.vue
│       │   └── ... (outras páginas do seu app)
│       ├── app.js
│       └── echo.js
├── routes/
│   ├── api.php (modificado)
│   ├── channels.php (modificado)
│   └── web.php (modificado)
├── .env (modificado)
├── composer.json
├── package.json (modificado)
├── vite.config.js
└── ... (outros arquivos do Laravel)
```

## 15. Considerações Finais e Próximos Passos

Este guia fornece uma base sólida para o seu sistema de chat. Para levá-lo para produção, considere:

- **Segurança:** Sempre use HTTPS. Configure corretamente seu servidor web (Nginx/Apache) para servir o Laravel e gerenciar certificados SSL.
- **Otimização:** Cache de rotas, configurações e views (`php artisan optimize`).
- **Monitoramento:** Implemente ferramentas de monitoramento para logs, performance da API e conexões WebSocket.
- **Escalabilidade:** Para um grande volume de mensagens, considere otimizações de banco de dados, sharding, e soluções de queue mais robustas (Redis, RabbitMQ).
- **Recursos Adicionais:**
    - Notificações push
    - Upload de arquivos/imagens
    - Typing indicators (indicadores de digitação)
    - Emojis e reações
    - Busca avançada de mensagens
    - Moderação de salas e usuários
    - Mensagens privadas (DM)
    - Suporte a múltiplos idiomas

Com este `README.md` completo, você tem todas as informações necessárias para construir e expandir seu sistema de chat em tempo real. Boa sorte!



## 13. Testando o Sistema

### 13.1. Frontend (Inertia)

1. Acesse `http://localhost:8000` (ou o URL configurado no seu `.env`).
2. Registre um novo usuário ou faça login.
3. Navegue para a rota `/chat`.
4. Crie uma nova sala de chat.
5. Abra a mesma sala em duas abas diferentes do navegador (ou com outro usuário).
6. Envie mensagens e observe a sincronização em tempo real.

### 13.2. API REST

Você pode usar ferramentas como Postman, Insomnia ou `curl` para testar a API.

#### 13.2.1. Verificar Status da API

```bash
curl http://localhost:8000/api/v1/status
```

Você deve receber uma resposta JSON com o status `online`.

#### 13.2.2. Registrar um Usuário via API

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Api User",
    "email": "apiuser@example.com",
    "password": "password",
    "password_confirmation": "password",
    "device_name": "Postman"
  }'
```

Copie o `token` da resposta. Ele será usado para as próximas requisições autenticadas.

#### 13.2.3. Criar uma Sala via API

```bash
curl -X POST http://localhost:8000/api/v1/rooms \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -d '{
    "name": "Sala da API",
    "description": "Sala criada via API REST",
    "is_private": false
  }'
```

#### 13.2.4. Enviar Mensagem via API

```bash
curl -X POST http://localhost:8000/api/v1/rooms/1/messages \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -d '{
    "content": "Olá do Postman!"
  }'
```

(Substitua `1` pelo ID da sala que você criou).

### 13.3. Testando o WebSocket (Clientes Externos)

Você pode usar um cliente WebSocket simples ou o exemplo em JavaScript/React fornecido na documentação da API para testar a conexão e o recebimento de mensagens em tempo real.

#### Exemplo de Configuração de Cliente JavaScript (fora do Laravel)

```javascript
// Certifique-se de ter instalado: npm install pusher-js
import Pusher from 'pusher-js';

const API_BASE_URL = 'http://localhost:8000/api/v1';
let authToken = 'SEU_TOKEN_AQUI'; // Obtenha este token do login da API

async function setupExternalChatClient() {
    // 1. Obter configurações do WebSocket e canais disponíveis
    const configResponse = await fetch(`${API_BASE_URL}/websocket/channels`, {
        headers: {
            'Authorization': `Bearer ${authToken}`,
            'Accept': 'application/json'
        }
    });
    const configData = await configResponse.json();
    const websocketConfig = configData.websocket_config;
    const userRooms = configData.data; // Salas que o usuário tem acesso

    // 2. Configurar Pusher
    const pusher = new Pusher(websocketConfig.key, {
        cluster: websocketConfig.cluster,
        wsHost: websocketConfig.host,
        wsPort: websocketConfig.port,
        wssPort: websocketConfig.port,
        forceTLS: websocketConfig.scheme === 'https', // Ajuste conforme seu esquema
        enabledTransports: ['ws', 'wss'],
        authEndpoint: `${API_BASE_URL}/websocket/auth`, // Endpoint de autenticação da API
        auth: {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        }
    });

    // 3. Inscrever-se nos canais das salas
    userRooms.forEach(room => {
        // Inscreva-se no canal privado da sala
        const privateChannelName = room.channels.private;
        const privateChannel = pusher.subscribe(privateChannelName);

        privateChannel.bind('message.sent', function(data) {
            console.log(`Nova mensagem na sala ${room.name}:`, data);
            // Atualize sua UI externa aqui
            displayExternalMessage(room.name, data);
        });

        console.log(`Inscrito no canal privado: ${privateChannelName}`);

        // Se a sala não for privada, você também pode se inscrever no canal público
        if (!room.is_private && room.channels.public) {
            const publicChannelName = room.channels.public;
            const publicChannel = pusher.subscribe(publicChannelName);
            publicChannel.bind('message.sent', function(data) {
                console.log(`Nova mensagem (pública) na sala ${room.name}:`, data);
                displayExternalMessage(room.name, data);
            });
            console.log(`Inscrito no canal público: ${publicChannelName}`);
        }
    });

    // Monitorar status da conexão
    pusher.connection.bind('connected', () => {
        console.log('Conectado ao WebSocket!');
    });

    pusher.connection.bind('disconnected', () => {
        console.log('Desconectado do WebSocket.');
    });

    pusher.connection.bind('error', (err) => {
        console.error('Erro no WebSocket:', err);
    });
}

function displayExternalMessage(roomName, message) {
    const messagesDiv = document.getElementById('external-messages');
    if (messagesDiv) {
        const msgElement = document.createElement('p');
        msgElement.textContent = `[${roomName}] ${message.user.name}: ${message.content} (${new Date(message.created_at).toLocaleTimeString()})`;
        messagesDiv.appendChild(msgElement);
        messagesDiv.scrollTop = messagesDiv.scrollHeight; // Scroll para o final
    }
}

// Chame esta função após obter o token de autenticação
// setupExternalChatClient();

// Exemplo de HTML para o cliente externo
/*
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>External Chat Client</title>
</head>
<body>
    <h1>External Chat Client</h1>
    <div id="external-messages" style="height: 300px; border: 1px solid #ccc; overflow-y: scroll; padding: 10px;"></div>
    <script type="module" src="./your-external-script.js"></script>
</body>
</html>
*/
```

## 14. Estrutura de Arquivos Final

Após seguir todos os passos, a estrutura de arquivos do seu projeto deve se assemelhar a:

```
seu-projeto/
├── app/
│   ├── Events/
│   │   ├── MessageSent.php
│   │   └── MessageSentApi.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── MessageApiController.php
│   │   │   │   ├── RoomApiController.php
│   │   │   │   └── WebSocketAuthController.php
│   │   │   ├── MessageController.php
│   │   │   └── RoomController.php
│   │   └── Middleware/
│   │       ├── ApiSecurityMiddleware.php
│   │       └── HandleInertiaRequests.php
│   └── Models/
│       ├── Message.php
│       ├── Room.php
│       └── User.php (modificado)
├── config/
│   ├── app.php (modificado)
│   ├── broadcasting.php (modificado)
│   ├── cors.php (modificado)
│   └── sanctum.php (modificado)
├── database/
│   └── migrations/
│       ├── ... (migrations padrão do Laravel)
│       ├── YYYY_MM_DD_HHMMSS_create_rooms_table.php
│       ├── YYYY_MM_DD_HHMMSS_create_messages_table.php
│       └── YYYY_MM_DD_HHMMSS_create_room_user_table.php
├── public/
├── resources/
│   └── js/
│       ├── Components/ (se usar Jetstream ou componentes próprios)
│       ├── Layouts/
│       │   └── AppLayout.vue
│       ├── Pages/
│       │   ├── Chat/
│       │   │   ├── Index.vue
│       │   │   └── Room.vue
│       │   └── ... (outras páginas do seu app)
│       ├── app.js
│       └── echo.js
├── routes/
│   ├── api.php (modificado)
│   ├── channels.php (modificado)
│   └── web.php (modificado)
├── .env (modificado)
├── composer.json
├── package.json (modificado)
├── vite.config.js
└── ... (outros arquivos do Laravel)
```

## 15. Considerações Finais e Próximos Passos

Este guia fornece uma base sólida para o seu sistema de chat. Para levá-lo para produção, considere:

- **Segurança:** Sempre use HTTPS. Configure corretamente seu servidor web (Nginx/Apache) para servir o Laravel e gerenciar certificados SSL.
- **Otimização:** Cache de rotas, configurações e views (`php artisan optimize`).
- **Monitoramento:** Implemente ferramentas de monitoramento para logs, performance da API e conexões WebSocket.
- **Escalabilidade:** Para um grande volume de mensagens, considere otimizações de banco de dados, sharding, e soluções de queue mais robustas (Redis, RabbitMQ).
- **Recursos Adicionais:**
    - Notificações push
    - Upload de arquivos/imagens
    - Typing indicators (indicadores de digitação)
    - Emojis e reações
    - Busca avançada de mensagens
    - Moderação de salas e usuários
    - Mensagens privadas (DM)
    - Suporte a múltiplos idiomas

Com este `README.md` completo, você tem todas as informações necessárias para construir e expandir seu sistema de chat em tempo real. Boa sorte!


