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

### 📚 Endpoints de Produtos

Todas as rotas abaixo exigem o header `Authorization: Bearer {access_token}`:

-   `GET /api/produtos` — Lista paginada ordenada por nome (`?search=` filtra por nome/descrição/categoria)
-   `POST /api/produtos` — Cadastra um produto (`nome`, `descricao`, `preco`, `categoria`, `estoque`)
-   `GET /api/produtos/{produto}` — Detalhes de um produto específico
-   `PUT /api/produtos/{produto}` — Atualiza qualquer campo informado
-   `DELETE /api/produtos/{produto}` — Remove o produto

### 🧱 Camadas de Serviço e Repositório

-   `app/Repositories/ProdutoRepositoryInterface.php` define o contrato para acesso a dados de produtos; `ProdutoRepository.php` implementa o CRUD e filtros usando Eloquent.
-   `app/Services/ProdutoService.php` concentra regras de negócio e orquestra o repositório, mantendo o controller fino.
-   O `ProdutoController` injeta o serviço, o que facilita testes unitários (mockando o contrato), reduz acoplamento com Eloquent e permite evoluir regras sem alterar a API.
-   Esta abordagem favorece coesão, reutilização e possibilidade de introduzir novas fontes de dados (ex.: cache, integrações externas) apenas trocando a implementação do repositório.

------------------------------------------------------------------------

## 🧪 Testes

Rodar todos os testes automatizados:

``` sh
docker exec -it laravel_app php artisan test
```

------------------------------------------------------------------------

## 📋 Funcionalidades Implementadas

-   [x] Autenticação (Laravel Breeze / Sanctum)\
-   [x] CRUD de Produtos\
-   [] Paginação e filtros avançados\
-   [] Logs assíncronos com Jobs + Redis\
-   [] Migrations, Seeders e Eloquent ORM\
-   [x] Validação com Form Requests\
-   [] Resources para padronização de resposta\
-   [] Tratamento de erros consistente

------------------------------------------------------------------------

## 🌟 Diferenciais

-   🔍 Busca inteligente com **Elasticsearch + Laravel Scout**\
-   🧑‍🔬 Testes com **PHPUnit**\
-   📏 Padronização de código com **Laravel Pint / PHP-CS-Fixer**\
-   🔒 Monitoramento de filas com **Laravel Horizon**

------------------------------------------------------------------------
