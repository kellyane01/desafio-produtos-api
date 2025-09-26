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

case "$role" in
  serve)
    wait_for_postgres

    echo "🗄️ Rodando migrations..."
    php artisan migrate --force

    echo "🌱 Executando seeders..."
    php artisan db:seed --force

    echo "🚀 Iniciando servidor Laravel..."
    exec php artisan serve --host=0.0.0.0 --port=8000
    ;;

  queue)
    wait_for_postgres
    echo "🎯 Iniciando worker de filas..."
    exec php artisan queue:work --tries=3 --timeout=90
    ;;

  *)
    exec "$@"
    ;;
esac
