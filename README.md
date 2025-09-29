# Desafio Técnico – Pessoa Desenvolvedora Back-end

API RESTful para gerenciamento de produtos construída em Laravel 10. Inclui autenticação, CRUD completo, filtros avançados, paginação configurável, logs assíncronos, busca inteligente com Elasticsearch e um ecossistema dockerizado pronto para rodar.

---

## TL;DR (Comece Por Aqui)
- ✅ API modular em Laravel + PostgreSQL + Redis + Elasticsearch
- 🔒 Autenticação via tokens (login, me, logout)
- 📦 CRUD completo de produtos com filtros, paginação e detalhamento
- 🧾 Logs assíncronos disparados por Jobs observando eventos do domínio
- 🧪 Testes com PHPUnit e padronização com Laravel Pint
- 🐳 Docker Compose sobe tudo com um único comando (`docker-compose up --build -d`)

---

## Sumário
- [Escopo do Desafio](#escopo-do-desafio)
- [Diferenciais Implementados](#diferenciais-implementados)
- [Checklist de Setup Rápido](#checklist-de-setup-rápido)
- [Variáveis de Ambiente Essenciais](#variáveis-de-ambiente-essenciais)
- [Serviços e Acessos](#serviços-e-acessos)
- [Autenticação Passo a Passo](#autenticação-passo-a-passo)
- [Test Drive da API](#test-drive-da-api)
- [Arquitetura e Organização](#arquitetura-e-organização)
- [Processamento Assíncrono e Observabilidade](#processamento-assíncrono-e-observabilidade)
- [Busca Inteligente](#busca-inteligente)
- [Uso do pgAdmin](#uso-do-pgadmin)
- [Testes Automatizados](#testes-automatizados)
- [Seeds e Carga Massiva](#seeds-e-carga-massiva)

---

## Escopo do Desafio
**Objetivo:** entregar uma API robusta, escalável e bem documentada para o domínio de produtos.

**Requisitos funcionais atendidos**
- Autenticação com login e proteção de todas as rotas de produtos.
- CRUD completo de produtos com campos id, nome, descricao, preco, categoria, estoque, created_at e updated_at.
- Listagem paginada, busca por nome e filtros por categoria, faixa de preço e disponibilidade.
- Endpoint dedicado para detalhar um produto.
- Registro de logs de criação, atualização e exclusão via Job assíncrono.

**Requisitos técnicos atendidos**
- Laravel 10 seguindo padrão REST (versionamento, status codes coerentes e responses padronizados via Resources).
- Banco PostgreSQL com migrations, seeders e Eloquent ORM.
- Form Requests para validação, camada de serviço e repositório para modularidade.
- Tratamento de erros consistente e observabilidade via logs dedicados.

**Critérios de avaliação cobertos**
- Código organizado em camadas (Controllers finos, Services, Repositories, Jobs, Observers).
- Estrutura modular pronta para escalar (contratos, filas, cache e indexação).
- Uso idiomático de Laravel + PostgreSQL + Redis + Elasticsearch.
- Documentação direcionada para onboarding rápido e entendimento das decisões técnicas.

---

## Diferenciais Implementados
- 🔍 Busca full-text com Elasticsearch 8, ranking customizado, sugestões e fallback relacional.
- 🚀 Processamento assíncrono de logs e sincronização de índices com Redis e múltiplos workers.
- 🧪 Testes automatizados com PHPUnit cobrindo autenticação, produtos, logs, observers e repositórios.
- 🧼 Padronização do código com Laravel Pint/PHP-CS-Fixer.
- 📊 Monitoramento de filas com Laravel Horizon.

---

## Checklist de Setup Rápido
- [ ] Docker e Docker Compose instalados
- [ ] Repositório clonado `git clone https://github.com/kellyane01/desafio-produtos-api`
- [ ] Arquivo `.env` criado (`cp .env.example .env`) e ajustado
- [ ] Containers no ar `docker-compose up --build -d`
- [ ] (Linux) `sudo sysctl -w vm.max_map_count=262144` antes do Elasticsearch
- [ ] API respondendo em http://localhost:8000

> Aguarde ate que os scripts sejam finalizados, acompanhe o processo nos logs do container *laravel_app*

> Quer ver os logs da fila? `docker-compose logs -f horizon`

---

## Variáveis de Ambiente Essenciais
```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=projeto_produtos
DB_USERNAME=laravel
DB_PASSWORD=secret

QUEUE_CONNECTION=redis
REDIS_HOST=redis
LOG_SEARCH_LEVEL=info

ELASTICSEARCH_HOSTS=http://elasticsearch:9200
ELASTICSEARCH_INDEX=produtos
PRODUTO_REINDEX_ON_BOOT=false
PRODUTO_SEED_ON_BOOT=false
```

---

## Serviços e Acessos
- API Laravel: http://localhost:8000
- Base path versionado: http://localhost:8000/api/v1
- pgAdmin: http://localhost:5050 (email `admin@admin.com`, senha `secret`)
- Credenciais semeadas: email `brena@gmail.com`, senha `12345678`
- Horizon Dashboard: http://localhost:8000/horizon (autenticado automaticamente em `APP_ENV=local`)

Após subir os containers, o Laravel executa migrations e seeds essenciais, aguardando PostgreSQL, Redis e Elasticsearch para iniciar de forma consistente.

---

## Autenticação Passo a Passo
1. `POST /api/v1/auth/login`
   ```json
   {
     "email": "brena@gmail.com",
     "password": "12345678"
   }
   ```
2. Leia o campo `access_token` da resposta e utilize em `Authorization: Bearer {token}`.
3. `GET /api/v1/auth/me` confirma o usuário logado.
4. `POST /api/v1/auth/logout` invalida o token atual.

> Todas as rotas de produtos e logs exigem token válido.

---

## Test Drive da API
```sh
# Listar produtos com filtros e paginação customizável
curl -X GET "http://localhost:8000/api/v1/produtos?nome=notebook&categoria=Eletronicos&preco_min=1000&preco_max=5000&per_page=5" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# Criar um produto
curl -X POST "http://localhost:8000/api/v1/produtos" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Notebook Gamer",
    "descricao": "Intel i7, 16GB RAM, RTX 4060",
    "preco": 8499.90,
    "categoria": "Eletronicos",
    "estoque": 15
  }'

# Consultar detalhes
curl -X GET "http://localhost:8000/api/v1/produtos/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

### Endpoints Principais
- `GET /api/v1/produtos` – Lista paginada com filtros por nome, categoria, disponibilidade e faixa de preço.
- `POST /api/v1/produtos` – Cria produto (Form Request valida payload).
- `GET /api/v1/produtos/{id}` – Detalhe completo do produto.
- `PUT /api/v1/produtos/{id}` – Atualiza registro (parcial ou total) e invalida caches relevantes.
- `DELETE /api/v1/produtos/{id}` – Remove produto e dispara log assíncrono.
- `GET /api/v1/logs` – Auditoria paginada com filtros por evento, usuário e período.

---

## Arquitetura e Organização
- PHP 8.2, Laravel 10 LTS, PostgreSQL 15, Redis e Elasticsearch.
- Docker Compose orquestra API, banco, cache, fila, pgAdmin e Horizon.
- Versionamento das rotas em `/api/v1`.
- Serviços em `app/Services`, Repositórios em `app/Repositories`, Observers em `app/Observers`.
- Controllers focados em orquestrar fluxo HTTP; regra de negócio concentrada em serviços.
- Cache com tags para evitar dados obsoletos após operações de escrita.

---

## Processamento Assíncrono e Observabilidade
- `ProdutoObserver` dispara `DispatchProdutoLogJob` para registrar ações no `LogRepository`.
- O serviço `horizon` executa `php artisan horizon` (spawn de workers automáticos).
- Monitoramento de filas via Laravel Horizon exposto em `/horizon` (restrições configuráveis via `HORIZON_ALLOWED_EMAILS`).
- `storage/logs/search.log` centraliza logs de busca; níveis ajustáveis via `LOG_SEARCH_LEVEL`.

### Horizon Dashboard
- Dashboard disponível em http://localhost:8000/horizon quando `APP_ENV=local`.
- Defina `HORIZON_ALLOWED_EMAILS` (lista separada por vírgula) para liberar acesso em outros ambientes.
- Ajuste quantidade de workers com `HORIZON_LOCAL_MAX_PROCESSES`, `HORIZON_MAX_PROCESSES` etc., conforme a necessidade.
- `docker-compose logs -f horizon` acompanha os supervisores e processos ativos.

---

## Busca Inteligente
- Elasticsearch indexa produtos automaticamente em background; jobs garantem sincronização incremental.
- `php artisan produto:search:reindex --fresh` recria mapeamento e reenfileira documentos (ou sem `--fresh` para apenas reindexar).
- Fallback seguro: indisponibilidade do Elasticsearch redireciona queries para PostgreSQL, invalida cache e registra evento.
- Configuração do índice via `ELASTICSEARCH_INDEX` e reindexação automática opt-in (`PRODUTO_REINDEX_ON_BOOT=true`).

---

## Uso do pgAdmin
1. Acesse http://localhost:5050 e autentique-se (`admin@admin.com` / `secret`).
2. Registre o servidor: host `postgres`, porta `5432`, database `projeto_produtos`, usuário `laravel`, senha `secret`.
3. Consulte dados
   ```sql
   SELECT * FROM produtos ORDER BY id DESC;
   ```

---

## Padronização do Código
- `composer lint` valida o projeto com o Laravel Pint (modo somente leitura).
- `composer lint:fix` aplica automaticamente os ajustes definidos em `pint.json`.
- Arquivos de build/cache (`bootstrap`, `storage`, `vendor`) ficam fora do escopo para evitar ruído.

Laravel Pint usa o preset oficial do framework em `pint.json`, então a estilização é consistente com o ecossistema do Laravel e compatível com o PHP-CS-Fixer.

---


## Testes Automatizados
```sh
./vendor/bin/phpunit
```

Rodando via Docker:
```sh
docker exec -it laravel_app ./vendor/bin/phpunit
```

Cobertura: autenticação, CRUD de produtos (filtros e ordenação), logs, observers, repositórios, jobs e busca.

---

## Seeds e Carga Massiva
```env
PRODUTO_SEED_TOTAL=100000
PRODUTO_SEED_BATCH=5000
```

```sh
# Popula grandes volumes manualmente (dentro do container)
docker exec -it laravel_app php artisan db:seed --class=ProdutoSeeder
```

> Defina `PRODUTO_SEED_ON_BOOT=true` apenas se quiser executar automaticamente ao subir os containers.

---
