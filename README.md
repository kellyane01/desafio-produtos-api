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
-   [Elasticsearch 8](https://www.elastic.co/elasticsearch/) (busca full-text)\
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

LOG_SEARCH_LEVEL=info
PRODUTO_SEED_ON_BOOT=false
PRODUTO_REINDEX_ON_BOOT=false
QUEUE_CONNECTION=redis
REDIS_HOST=redis

# Busca inteligente com Elasticsearch
ELASTICSEARCH_HOSTS=http://elasticsearch:9200
ELASTICSEARCH_INDEX=produtos
```

### 3. Subir containers

``` sh
docker-compose up --build --scale worker=3 -d
```

> ℹ️ Se estiver rodando em Linux, garanta que `vm.max_map_count` esteja configurado para, pelo menos, `262144` (ex.: `sudo sysctl -w vm.max_map_count=262144`) antes de subir o container do Elasticsearch.

### 📨 Fila de Jobs

- O container `worker` executa `php artisan queue:work` conectado ao Redis e processa os logs de forma assíncrona.
- O mesmo worker também sincroniza os documentos de produtos no Elasticsearch.
- Ele aguarda o Elasticsearch antes de iniciar, garantindo que jobs de busca não falhem por indisponibilidade do cluster.
- Para acompanhar os jobs em execução, use `docker logs -f <nome-do-container>` (ex.: `docker logs -f produto-api-worker-1`).
- Caso rode fora do Docker, certifique-se de iniciar manualmente um worker com `php artisan queue:work redis --tries=3 --timeout=90`.

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

### 🔢 Versionamento da API

-   Base path das rotas autenticadas: `http://localhost:8000/api/v1`.
-   Novas versões serão expostas como `/api/v{n}/...`, permitindo evolução sem afetar clientes existentes.
-   As tabelas abaixo já refletem os endpoints disponíveis na versão `v1`.

### 🔐 Autenticação via Token

-   Faça `POST http://localhost:8000/api/v1/auth/login` com `Accept: application/json` e corpo JSON:

    ```json
    {
      "email": "brena@gmail.com",
      "password": "12345678"
    }
    ```

-   A resposta retorna `access_token`; envie-o como `Authorization: Bearer {access_token}` para chamar rotas protegidas (ex.: `GET /api/v1/auth/me`).
-   Para encerrar a sessão do token atual, chame `POST http://localhost:8000/api/v1/auth/logout` com o header `Authorization` informado acima.
-   O endpoint `GET http://localhost:8000/api/v1/auth/me` retorna o usuário autenticado e é útil para validar o token.

## 📘 Documentação da API

-   A documentação interativa é gerada com [Scribe](https://scribe.knuckles.wtf/docs/laravel). Após instalar as dependências, execute `php artisan scribe:generate` sempre que alterar as rotas ou recursos da API.
-   Visualize o portal em `http://localhost:8000/docs`. O endpoint é exposto automaticamente com assets versionados em `public/vendor/scribe`.
-   A coleção Postman é salva em `storage/app/scribe/collection.json`. Importe esse arquivo no Postman para testar os endpoints.
-   A especificação OpenAPI (v3) fica disponível em `storage/app/scribe/openapi.yaml`.
-   Defina `APP_URL` e, opcionalmente, `SCRIBE_AUTH_BEARER_TOKEN` no `.env` para que o botão **Try it out** pré-carregue a URL base e um token válido.
-   A rota raiz (`/`) redireciona automaticamente para `/docs`, facilitando o acesso ao portal.

### 📚 Endpoints de Produtos

Todas as rotas abaixo exigem o header `Authorization: Bearer {access_token}`:

-   `GET /api/v1/produtos` — Lista paginada com filtros e ordenacao (detalhes abaixo)
-   `POST /api/v1/produtos` — Cadastra um produto (`nome`, `descricao`, `preco`, `categoria`, `estoque`)
-   `GET /api/v1/produtos/{produto}` — Detalhes de um produto específico
-   `PUT /api/v1/produtos/{produto}` — Atualiza qualquer campo informado
-   `DELETE /api/v1/produtos/{produto}` — Remove o produto

#### ⚙️ Parametros de consulta (`GET /api/v1/produtos`)

Combine os parametros conforme necessario. Exemplos na sequencia.

| Parametro     | Tipo        | Descricao                                                                                       |
|---------------|-------------|------------------------------------------------------------------------------------------------|
| `page`        | int         | Numero da pagina (comeca em 1). Default: pagina atual resolvida pelo Laravel.                   |
| `per_page`    | int         | Quantidade de itens por pagina. Valores <= 0 voltam ao default de 15.                           |
| `search`      | string      | Busca full-text (Elasticsearch) com fuzziness em `nome`, `descricao` e `categoria`, retornando sugestões em `meta.search.suggestions`. |
| `categoria`   | string      | Filtra por igualdade exata da categoria.                                                        |
| `categorias`  | string/array| Filtra por multiplas categorias (separadas por virgula ou enviadas como array).               |
| `min_preco`   | float       | Filtra produtos com preco maior ou igual ao informado.                                          |
| `max_preco`   | float       | Filtra produtos com preco menor ou igual ao informado.                                          |
| `disponivel`  | bool        | `true` para estoque > 0, `false` para estoque <= 0.                                             |
| `sort`        | string      | Coluna de ordenacao (`nome`, `preco`, `categoria`, `estoque`, `created_at`). Default: `nome`.   |
| `order`       | string      | Direcao da ordenacao (`asc` ou `desc`). Default: `asc`.                                         |

Exemplos:

```http
GET /api/v1/produtos?page=2

GET /api/v1/produtos?sort=preco&order=desc

GET /api/v1/produtos?categoria=eletronicos&min_preco=100&per_page=15&page=3

GET /api/v1/produtos?categorias=eletronicos,acessorios&disponivel=true
```

Quando a busca full-text é aplicada, a resposta adiciona um bloco
`meta.search` com o motor utilizado, pontuação máxima e sugestões
relevantes, além de destacar os termos encontrados em cada item via o
campo opcional `search_highlight`:

```json
{
  "data": [
    {
      "id": 42,
      "nome": "Notebook Gamer Z15",
      "descricao": "GPU dedicada com 8 GB",
      "preco": 7999.9,
      "categoria": "Eletrônicos",
      "estoque": 12,
      "search_highlight": {
        "nome": "Notebook <em>Gamer</em> Z15",
        "descricao": "GPU dedicada com <em>8</em> GB"
      }
    }
  ],
  "meta": {
    "search": {
      "engine": "elasticsearch",
      "max_score": 8.91,
      "suggestions": [
        "notebook gamer",
        "notebook games"
      ]
    }
  }
}
```

#### 🚀 Cache de resultados

- Os resultados paginados sao armazenados no Redis usando tags `produtos` por 5 minutos.
- Qualquer combinacao de parametros (`search`, `categoria`, `categorias`, `min_preco`, `max_preco`, `disponivel`, `sort`, `order`, `per_page`, `page`) gera uma chave unica. Consultas repetidas dentro do TTL retornam a resposta em cache, reduzindo leituras no banco.

### 📜 Logs de Auditoria

-   `GET /api/v1/logs` — Lista paginada das ações registradas pelo sistema. Útil para auditoria e debugging de mudanças em produtos.
- Operacoes de escrita (`POST`, `PUT`, `DELETE`) invalidam a tag `produtos`, garantindo que novas consultas tragam dados atualizados.
- Certifique-se de que o cache padrao (`CACHE_STORE` no `.env`) esteja configurado para Redis em ambientes que devem se beneficiar do cache.

### 🧱 Camadas de Serviço e Repositório

-   `app/Repositories/ProdutoRepositoryInterface.php` define o contrato para acesso a dados de produtos; `ProdutoRepository.php` implementa o CRUD e filtros usando Eloquent.
-   `app/Services/ProdutoService.php` concentra regras de negócio e orquestra o repositório, mantendo o controller fino.
-   O `ProdutoController` injeta o serviço, o que facilita testes unitários (mockando o contrato), reduz acoplamento com Eloquent e permite evoluir regras sem alterar a API.
-   Esta abordagem favorece coesão, reutilização e possibilidade de introduzir novas fontes de dados (ex.: cache, integrações externas) apenas trocando a implementação do repositório.

------------------------------------------------------------------------

## 🧪 Testes

Execute a suíte completa (usa SQLite em memória e drivers de cache/fila em array):

``` sh
./vendor/bin/phpunit
```

Rodando dentro dos containers:

``` sh
docker exec -it laravel_app ./vendor/bin/phpunit
```

Principais cenários cobertos:

- API de produtos (filtros, validações, ordenação e CRUD)
- Autenticação via token (login e logout)
- Listagem de logs com filtros e paginação
- Observer de produtos disparando o job de auditoria
- Repositório de produtos com regras de consulta

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

### 🔄 Reindexar produtos no Elasticsearch

Após popular ou alterar em massa os dados, execute a reindexação para
garantir que o Elasticsearch reflita o estado atual da base:

```sh
# Dentro do container
php artisan produto:search:reindex --fresh

# A partir da máquina host
docker exec -it laravel_app php artisan produto:search:reindex --fresh
```

Use `--fresh` para recriar o índice (útil ao alterar mapeamento). Sem esse
sinalizador o comando apenas garante a existência do índice e reenvia os
documentos em lotes de 500 registros (configurável via `--chunk`).

> Para automatizar a reindexação junto com o boot dos containers, defina
> `PRODUTO_REINDEX_ON_BOOT=true` no `.env`. O entrypoint aguardará o
> Elasticsearch ficar disponível antes de rodar `produto:search:reindex`.

### 📈 Observabilidade da busca

- Eventos de indisponibilidade e recuperação do Elasticsearch são
  registrados em `storage/logs/search.log` (ajuste o nível com
  `LOG_SEARCH_LEVEL`).
- O status recente do cluster fica em cache (`search:elasticsearch:status`),
  permitindo instrumentar health checks ou dashboards a partir desse dado.
- Quando o Elasticsearch estiver indisponível, a API volta a consultar o
  banco relacional, limpa o cache da busca e registra o motivo da falha;
  quando o cluster normalizar, os logs registrarão o retorno à condição
  saudável.

------------------------------------------------------------------------

## 📋 Funcionalidades Implementadas

-   [x] Autenticação (Laravel Breeze / Sanctum)\
-   [x] CRUD de Produtos\
-   [x] Paginação e filtros avançados\
-   [x] Busca full-text com Elasticsearch (fuzzy, sugestões e ranking)\
-   [x] Logs assíncronos com Jobs + Redis\
-   [x] Fallback consciente para busca (cache invalidado e monitoramento)\
-   [x] Migrations, Seeders e Eloquent ORM\
-   [x] Validação com Form Requests e mensagens personalizadas em português\
-   [x] Resources para padronização de resposta\
-   [x] Tratamento de erros consistente
-   [x] Testes automatizados

------------------------------------------------------------------------

## 🌟 Diferenciais

-   🔍 Busca inteligente com **Elasticsearch + ranking personalizado**\
-   🧑‍🔬 Testes com **PHPUnit**\
-   📏 Padronização de código com **Laravel Pint / PHP-CS-Fixer**\
-   🔒 Monitoramento de filas com **Laravel Horizon**

------------------------------------------------------------------------
