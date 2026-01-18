#!/bin/bash

echo "=== Debut du backup Serveur Web ==="
echo "Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
echo "Hostname: $(hostname)"
echo "IP: $(hostname -I | awk '{print $1}')"
echo ""

echo "-> Verification de l'espace disque..."
df -h / | tail -1

echo ""
echo "-> Backup des fichiers web en cours..."

WEB_DIR="/var/www/html"
BACKUP_DIR="/tmp/backups"
TIMESTAMP=$(date '+%Y%m%d_%H%M%S')

mkdir -p $BACKUP_DIR

if [ -d "$WEB_DIR" ]; then
    tar -czf $BACKUP_DIR/web_backup_$TIMESTAMP.tar.gz -C /var/www html 2>/dev/null
    echo "[OK] Backup cree: web_backup_$TIMESTAMP.tar.gz"
    echo "     Taille: $(du -h $BACKUP_DIR/web_backup_$TIMESTAMP.tar.gz | cut -f1)"
else
    echo "[WARN] Repertoire $WEB_DIR non trouve"
fi

echo ""
echo "=== Backup termine avec succes ==="
