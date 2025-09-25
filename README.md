# 🛠️ Desafio Técnico -- Backend (Laravel + PostgreSQL)

API RESTful para gerenciamento de **produtos**, desenvolvida em
**Laravel 10**, com autenticação, CRUD completo, filtros, paginação e
logs assíncronos.

------------------------------------------------------------------------

## 🚀 Tecnologias

-   [Laravel 10 LTS](https://laravel.com/)\
-   [PHP 8.2](https://www.php.net/releases/8.2/)\
-   [PostgreSQL 15](https://www.postgresql.org/)\
-   [Redis](https://redis.io/) (cache e filas de jobs)\
-   [Docker + Docker Compose](https://www.docker.com/)\
-   [pgAdmin](https://www.pgadmin.org/) (gerenciar o banco)

------------------------------------------------------------------------

## 📦 Subindo o Projeto

### 1. Clonar repositório

``` sh
git clone https://github.com/kellyane01/desafio-produtos-api
cd desafio-produtos-api
```

### 2. Copiar variáveis de ambiente

``` sh
cp .env.example .env
```

Edite o `.env` se necessário. Configuração padrão para containers:

``` env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=projeto_produtos
DB_USERNAME=laravel
DB_PASSWORD=secret

QUEUE_CONNECTION=redis
REDIS_HOST=redis
```

### 3. Subir containers

``` sh
docker-compose up --build -d
```

### 4. Acessar o projeto

-   API: <http://localhost:8000>\
-   pgAdmin: <http://localhost:5050>
    -   **Email:** `admin@admin.com`\
    -   **Senha:** `secret`

Credenciais padrão da API:

-   **Email:** `brena@gmail.com`
-   **Senha:** `12345678`

### 🔐 Autenticação via Token

-   Faça `POST http://localhost:8000/api/login` com `Accept: application/json` e corpo JSON:

    ```json
    {
      "email": "brena@gmail.com",
      "password": "12345678"
    }
    ```

-   A resposta retorna `access_token`; envie-o como `Authorization: Bearer {access_token}` para chamar rotas protegidas (ex.: `GET /api/user`).
-   Para encerrar a sessão do token atual, chame `POST http://localhost:8000/api/logout` com o header `Authorization` informado acima.

------------------------------------------------------------------------

## 🧪 Testes

Rodar todos os testes automatizados:

``` sh
docker exec -it laravel_app php artisan test
```

------------------------------------------------------------------------

## 📋 Funcionalidades Implementadas

-   [x] Autenticação (Laravel Breeze / Sanctum)\
-   [] CRUD de Produtos\
-   [] Paginação e filtros avançados\
-   [] Logs assíncronos com Jobs + Redis\
-   [] Migrations, Seeders e Eloquent ORM\
-   [] Validação com Form Requests\
-   [] Resources para padronização de resposta\
-   [] Tratamento de erros consistente

------------------------------------------------------------------------

## 🌟 Diferenciais

-   🔍 Busca inteligente com **Elasticsearch + Laravel Scout**\
-   🧑‍🔬 Testes com **PHPUnit**\
-   📏 Padronização de código com **Laravel Pint / PHP-CS-Fixer**\
-   🔒 Monitoramento de filas com **Laravel Horizon**

------------------------------------------------------------------------
