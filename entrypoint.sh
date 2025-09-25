#!/bin/bash
set -e

# Instalar dependências só se não existirem (primeiro run)
if [ ! -d "vendor" ]; then
  echo "📦 Instalando dependências com Composer..."
  composer install
fi

# Gerar APP_KEY se ainda não existir
if ! grep -q "APP_KEY=" .env || [ -z "$(grep APP_KEY= .env | cut -d '=' -f2)" ]; then
  echo "⚡ Gerando APP_KEY..."
  php artisan key:generate --force
else
  echo "✅ APP_KEY já configurada."
fi

# Aguardar Postgres aceitar conexões antes das migrations
DB_HOST=${DB_HOST:-postgres}
DB_PORT=${DB_PORT:-5432}
echo "⏳ Aguardando Postgres em ${DB_HOST}:${DB_PORT}..."
until php -r "exit((int)!@fsockopen('${DB_HOST}', ${DB_PORT}));"; do
  echo "🔁 Postgres indisponível, tentando novamente em 1s..."
  sleep 1
done
echo "✅ Postgres disponível."

# Rodar migrations e seeders
echo "🗄️ Rodando migrations..."
php artisan migrate --force

echo "🌱 Executando seeders..."
php artisan db:seed --force

# Iniciar servidor
echo "🚀 Iniciando servidor Laravel..."
php artisan serve --host=0.0.0.0 --port=8000
