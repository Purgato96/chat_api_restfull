[![License](https://img.shields.io/github/license/Purgato96/chat_api_restfull)](https://github.com/Purgato96/chat_api_restfull/blob/main/LICENSE)
[![Last Commit](https://img.shields.io/github/last-commit/Purgato96/chat_api_restfull)](https://github.com/Purgato96/chat_api_restfull/commits/main)
[![Stars](https://img.shields.io/github/stars/Purgato96/chat_api_restfull)](https://github.com/Purgato96/chat_api_restfull/stargazers)
[![Issues](https://img.shields.io/github/issues/Purgato96/chat_api_restfull)](https://github.com/Purgato96/chat_api_restfull/issues)
![Laravel](https://img.shields.io/badge/Laravel-12-red)
![PHP](https://img.shields.io/badge/PHP-8.4-blue)

# Chat API RESTful

[🇺🇸 English](#-english) | [🇧🇷 Português](#-português)

---

## 🇺🇸 English

Real-time chat application built with **Laravel** and **Vue.js** (via Inertia).  
Provides a REST API secured with Laravel Sanctum and WebSocket integration using Pusher.

### Features
- User registration and authentication
- Public and private room creation
- Sending and editing messages with real-time broadcast
- REST endpoints for rooms and messages

### Installation
```bash 
  composer install
  npm install
```

### Configuration
1. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```
2. Set your database and Pusher credentials in `.env`.
3. Generate the app key:
   ```bash
   php artisan key:generate
   ```
4. Run migrations:
   ```bash
   php artisan migrate
   ```

### Development Environment
Run the Laravel server and Vite in parallel:
```bash
  composer run dev
```

### Production
Build the assets and use a web server (Nginx/Apache):
```bash
  npm run build
  php artisan serve --env=production
```

### Testing
Run the test suite:
```bash
  php artisan test
```

### Request Examples
#### Login
```http
POST /api/v1/auth/login
{
  "email": "user@example.com",
  "password": "secret",
  "device_name": "cli"
}
```
#### List Rooms
```http
GET /api/v1/rooms
Authorization: Bearer <token>
```
#### Send Message
```http
POST /api/v1/rooms/{room}/messages
Authorization: Bearer <token>
{
  "content": "Hello"
}
```

### 🌐 Live API Endpoints

Production environment:

- **Register:** [`POST /api/v1/register`](https://basedopurga.com.br/api/v1/register)
- **Login:** [`POST /api/v1/login`](https://basedopurga.com.br/api/v1/login)

**API Base URL:** [https://basedopurga.com.br/api/v1](https://basedopurga.com.br/api/v1)

> ℹ️ Additional endpoints for rooms and messages are also available in the production API.

### Tech Stack
- PHP 8 / Laravel
- Vue 3 + Inertia
- Laravel Sanctum
- Pusher
- Vite + Tailwind CSS

### Contributing
Pull requests are welcome. Please open issues to suggest improvements or report bugs.

### License
Distributed under the MIT License. See `composer.json` for details.

---

## 🇧🇷 Português

Aplicação de chat em tempo real construída com **Laravel** e **Vue.js** (via Inertia).  
Oferece uma API REST protegida com Laravel Sanctum e integração WebSocket usando Pusher.

### Funcionalidades
- Cadastro e autenticação de usuários
- Criação de salas públicas ou privadas
- Envio e edição de mensagens com broadcast em tempo real
- Endpoints REST para salas e mensagens

### Instalação
```bash
  composer install
  npm install
```

### Configuração
1. Copie `.env.example` para `.env`:
   ```bash
   cp .env.example .env
   ```
2. Defina as credenciais do banco de dados e do Pusher no `.env`.
3. Gere a chave da aplicação:
   ```bash
   php artisan key:generate
   ```
4. Execute as migrações:
   ```bash
   php artisan migrate
   ```

### Ambiente de desenvolvimento
Execute o servidor Laravel e o Vite em paralelo:
```bash
  composer run dev
```

### Produção
Compile os assets e use um servidor web (Nginx/Apache):
```bash
  npm run build
php artisan serve --env=production
```

### Testes
Para rodar a suíte de testes:
```bash
  php artisan test
```

### Exemplos de requisição
#### Login
```http
POST /api/v1/auth/login
{
  "email": "user@example.com",
  "password": "secret",
  "device_name": "cli"
}
```
#### Listar salas
```http
GET /api/v1/rooms
Authorization: Bearer <token>
```
#### Enviar mensagem
```http
POST /api/v1/rooms/{room}/messages
Authorization: Bearer <token>
{
  "content": "Olá"
}
```

### 🌐 Endpoints da API em Produção

Ambiente de produção:

- **Registrar:** [`POST /api/v1/register`](https://basedopurga.com.br/api/v1/register)
- **Login:** [`POST /api/v1/login`](https://basedopurga.com.br/api/v1/login)

**URL base da API:** [https://basedopurga.com.br/api/v1](https://basedopurga.com.br/api/v1)

> ℹ️ Outros endpoints para salas e mensagens também estão disponíveis na API em produção.

### Tecnologias
- PHP 8 / Laravel
- Vue 3 + Inertia
- Laravel Sanctum
- Pusher
- Vite + Tailwind CSS

### Contribuição
Pull requests são bem-vindos. Abra issues para sugerir melhorias ou reportar bugs.

### Licença
Distribuído sob a licença MIT. Veja `composer.json` para mais detalhes.
