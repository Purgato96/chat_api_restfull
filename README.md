# Chat API RESTful

Aplicação de chat em tempo real construída com **Laravel** e **Vue.js** (via Inertia).
Oferece uma API REST protegida com Laravel Sanctum e integração WebSocket usando Pusher.

## Funcionalidades
- Cadastro e autenticação de usuários
- Criação de salas públicas ou privadas
- Envio e edição de mensagens com broadcast em tempo real
- Endpoints REST para salas e mensagens

## Instalação
```bash
composer install
npm install
```

## Configuração
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

## Ambiente de desenvolvimento
Execute o servidor Laravel e o Vite em paralelo:
```bash
composer run dev
```

## Produção
Compile os assets e use um servidor web (Nginx/Apache) para rodar o projeto:
```bash
npm run build
php artisan serve --env=production
```

## Testes
Para rodar a suíte de testes:
```bash
php artisan test
```

## Exemplos de requisição
### Login
```http
POST /api/v1/auth/login
{
  "email": "user@example.com",
  "password": "secret",
  "device_name": "cli"
}
```
### Listar salas
```http
GET /api/v1/rooms
Authorization: Bearer <token>
```
### Enviar mensagem
```http
POST /api/v1/rooms/{room}/messages
Authorization: Bearer <token>
{
  "content": "Olá"
}
```

## Tecnologias
- PHP 8 / Laravel
- Vue 3 + Inertia
- Laravel Sanctum
- Pusher (broadcast)
- Vite + Tailwind CSS

## Contribuição
Pull requests são bem-vindos. Abra issues para sugerir melhorias ou reportar bugs.

## Licença
Distribuído sob a licença MIT. Veja `composer.json` para mais detalhes.
