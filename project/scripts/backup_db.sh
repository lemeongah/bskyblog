#!/bin/bash
set -e

cd "$(dirname "$0")/.."
source .env

BACKUP_DIR="./backups"
mkdir -p "$BACKUP_DIR"

DATE=$(date +"%Y-%m-%d_%H-%M")
BACKUP_FILE="$BACKUP_DIR/${DB_NAME}_${DATE}.sql.gz"

echo "📦 Sauvegarde de la base '$DB_NAME'..."

docker compose exec db sh -c "exec mysqldump -u$DB_USER -p$DB_PASSWORD $DB_NAME" | gzip > "$BACKUP_FILE"

echo "✅ Sauvegarde compressée : $BACKUP_FILE"

# 🔁 Nettoyage : garder uniquement les 10 plus récentes
echo "🧹 Nettoyage : suppression des anciennes sauvegardes..."
ls -1t "$BACKUP_DIR"/${DB_NAME}_*.sql.gz | tail -n +11 | xargs -r rm -f

echo "🧼 Fichiers restants :"
ls -lh "$BACKUP_DIR" | grep "$DB_NAME"
