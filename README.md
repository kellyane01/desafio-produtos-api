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

### 🖧 Conectar o pgAdmin ao PostgreSQL

1. Abra o pgAdmin (<http://localhost:5050>) e autentique-se com o usuário admin informado acima.
2. Clique com o botão direito em **Servers** \> **Register** \> **Server...**.
3. Na aba **General**, defina um nome descritivo como `Postgres (Docker)`.
4. Na aba **Connection**, use os valores abaixo e clique em **Save**:
   -   **Host name/address:** `postgres`
   -   **Port:** `5432`
   -   **Maintenance database:** `projeto_produtos`
   -   **Username:** `laravel`
   -   **Password:** `secret`

Esses dados correspondem ao container `postgres` definido no `docker-compose.yml`. Quando a ligação estiver ativa, o banco `projeto_produtos` aparecerá dentro do grupo **Databases** (ou **Bancos de dados**, se o pgAdmin estiver em português) no servidor recém-registrado.

### 🗂️ Visualizar os dados da tabela `produtos`

1. No pgAdmin, expanda `Servers` \> `Postgres (Docker)` (ou o nome que escolheu) \> `Databases` \> `projeto_produtos`.
2. Siga por `Schemas` \> `public` \> `Tables` e localize `produtos`.
3. Clique com o botão direito em `produtos` \> **View/Edit Data** \> **All Rows** para abrir um grid com todos os registros.
4. Se preferir uma consulta manual, abra **Query Tool** no mesmo banco e execute:

    ```sql
    SELECT * FROM produtos ORDER BY id DESC;
    ```

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

-   `GET /api/produtos` — Lista paginada com filtros e ordenacao (detalhes abaixo)
-   `POST /api/produtos` — Cadastra um produto (`nome`, `descricao`, `preco`, `categoria`, `estoque`)
-   `GET /api/produtos/{produto}` — Detalhes de um produto específico
-   `PUT /api/produtos/{produto}` — Atualiza qualquer campo informado
-   `DELETE /api/produtos/{produto}` — Remove o produto

#### ⚙️ Parametros de consulta (`GET /api/produtos`)

Combine os parametros conforme necessario. Exemplos na sequencia.

| Parametro     | Tipo        | Descricao                                                                                       |
|---------------|-------------|------------------------------------------------------------------------------------------------|
| `page`        | int         | Numero da pagina (comeca em 1). Default: pagina atual resolvida pelo Laravel.                   |
| `per_page`    | int         | Quantidade de itens por pagina. Valores <= 0 voltam ao default de 15.                           |
| `search`      | string      | Busca parcial em `nome`, `descricao` e `categoria`.                                             |
| `categoria`   | string      | Filtra por igualdade exata da categoria.                                                        |
| `categorias`  | string/array| Filtra por multiplas categorias (separadas por virgula ou enviadas como array).               |
| `min_preco`   | float       | Filtra produtos com preco maior ou igual ao informado.                                          |
| `max_preco`   | float       | Filtra produtos com preco menor ou igual ao informado.                                          |
| `disponivel`  | bool        | `true` para estoque > 0, `false` para estoque <= 0.                                             |
| `sort`        | string      | Coluna de ordenacao (`nome`, `preco`, `categoria`, `estoque`, `created_at`). Default: `nome`.   |
| `order`       | string      | Direcao da ordenacao (`asc` ou `desc`). Default: `asc`.                                         |

Exemplos:

```http
GET /api/produtos?page=2

GET /api/produtos?sort=preco&order=desc

GET /api/produtos?categoria=eletronicos&min_preco=100&per_page=15&page=3

GET /api/produtos?categorias=eletronicos,acessorios&disponivel=true
```

#### 🚀 Cache de resultados

- Os resultados paginados sao armazenados no Redis usando tags `produtos` por 5 minutos.
- Qualquer combinacao de parametros (`search`, `categoria`, `categorias`, `min_preco`, `max_preco`, `disponivel`, `sort`, `order`, `per_page`, `page`) gera uma chave unica. Consultas repetidas dentro do TTL retornam a resposta em cache, reduzindo leituras no banco.
- Operacoes de escrita (`POST`, `PUT`, `DELETE`) invalidam a tag `produtos`, garantindo que novas consultas tragam dados atualizados.
- Certifique-se de que o cache padrao (`CACHE_STORE` no `.env`) esteja configurado para Redis em ambientes que devem se beneficiar do cache.

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

## 📈 Carga Massiva de Produtos

Os testes de desempenho exigem grandes volumes de produtos. Ajuste, no
`.env`, as variáveis abaixo (os valores padrão criam 100.000 itens em
lotes de 5.000):

``` env
PRODUTO_SEED_TOTAL=100000
PRODUTO_SEED_BATCH=5000
# Deixa false para não executar automaticamente ao subir containers
PRODUTO_SEED_ON_BOOT=false
```

Para iniciar a carga maciça (80–90% com estoque regular e o restante com
estoque elevado), execute um dos comandos conforme o ambiente:

``` sh
# Dentro do container
php artisan db:seed --class=ProdutoSeeder

# Via Docker a partir da máquina host
docker exec -it laravel_app php artisan db:seed --class=ProdutoSeeder
```

O seeder informa o progresso no console; aumente ou reduza os números
acima para testar diferentes volumes.

> Observação: apenas a seed de usuário roda automaticamente. Para acionar a
> seed de produtos na inicialização, defina `PRODUTO_SEED_ON_BOOT=true`.

------------------------------------------------------------------------

## 📋 Funcionalidades Implementadas

-   [x] Autenticação (Laravel Breeze / Sanctum)\
-   [x] CRUD de Produtos\
-   [x] Paginação e filtros avançados\
-   [] Logs assíncronos com Jobs + Redis\
-   [x] Migrations, Seeders e Eloquent ORM\
-   [x] Validação com Form Requests e mensagens personalizadas em português\
-   [x] Resources para padronização de resposta\
-   [x] Tratamento de erros consistente

------------------------------------------------------------------------

## 🌟 Diferenciais

-   🔍 Busca inteligente com **Elasticsearch + Laravel Scout**\
-   🧑‍🔬 Testes com **PHPUnit**\
-   📏 Padronização de código com **Laravel Pint / PHP-CS-Fixer**\
-   🔒 Monitoramento de filas com **Laravel Horizon**

------------------------------------------------------------------------
