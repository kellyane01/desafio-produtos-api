#!/bin/bash
set -e

role=${1:-serve}

# Instalar dependências só se não existirem
if [ ! -d "vendor" ]; then
  echo "📦 Instalando dependências com Composer..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Gerar APP_KEY se ainda não existir
if ! grep -q "APP_KEY=" .env || [ -z "$(grep APP_KEY= .env | cut -d '=' -f2)" ]; then
  echo "⚡ Gerando APP_KEY..."
  php artisan key:generate --force
else
  echo "✅ APP_KEY já configurada."
fi

# Função para esperar o Postgres ficar disponível
wait_for_postgres() {
  DB_HOST=${DB_HOST:-postgres}
  DB_PORT=${DB_PORT:-5432}
  echo "⏳ Aguardando Postgres em ${DB_HOST}:${DB_PORT}..."
  until php -r "exit((int)!@fsockopen('${DB_HOST}', ${DB_PORT}));"; do
    echo "🔁 Postgres indisponível, tentando novamente em 1s..."
    sleep 1
  done
  echo "✅ Postgres disponível."
}

wait_for_redis() {
  REDIS_HOST=${REDIS_HOST:-redis}
  REDIS_PORT=${REDIS_PORT:-6379}
  echo "⏳ Aguardando Redis em ${REDIS_HOST}:${REDIS_PORT}..."
  until php -r "exit((int)!@fsockopen('${REDIS_HOST}', ${REDIS_PORT}));"; do
    echo "🔁 Redis indisponível, tentando novamente em 1s..."
    sleep 1
  done
  echo "✅ Redis disponível."
}

case "$role" in
  serve)
    wait_for_postgres
    wait_for_redis

    echo "🗄️ Rodando migrations..."
    php artisan migrate --force

    should_seed=$(printf '%s' "${PRODUTO_SEED_ON_BOOT:-false}" | tr '[:upper:]' '[:lower:]')
    if [ "${should_seed}" = "true" ] || [ "${should_seed}" = "1" ] || [ "${should_seed}" = "yes" ]; then
      echo "🌱 Executando seeders..."
      php artisan db:seed --force
    else
      echo "🌱 Seeders ignorados no boot (PRODUTO_SEED_ON_BOOT=${PRODUTO_SEED_ON_BOOT:-false})."
    fi

    echo "🚀 Iniciando servidor Laravel..."
    exec php artisan serve --host=0.0.0.0 --port=8000
    ;;

  queue)
    wait_for_redis
    wait_for_postgres
    echo "🎯 Iniciando worker de filas..."
    exec php artisan queue:work --tries=3 --timeout=90
    ;;

  *)
    exec "$@"
    ;;
esac
