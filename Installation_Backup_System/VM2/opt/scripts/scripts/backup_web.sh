#!/bin/bash
# Script de backup du Serveur Web (VM2)

echo "=== Debut du backup Serveur Web ==="
echo "Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
echo "Hostname: $(hostname)"
echo "IP: $(hostname -I | awk '{print $1}')"
echo ""

# Configuration du serveur de backup
BACKUP_SERVER="10.3.123.20"
BACKUP_USER="rt"
BACKUP_PASSWORD="alexandre"
BACKUP_REMOTE_DIR="/home/rt/backups/serveur_web"

# Verification de l'espace disque
echo "-> Verification de l'espace disque..."
df -h / | tail -1

echo ""
echo "-> Backup des fichiers web en cours..."

# Backup du repertoire web
WEB_DIR="/var/www/html"
BACKUP_DIR="/tmp/backups"
TIMESTAMP=$(date '+%Y%m%d_%H%M%S')
BACKUP_FILE="web_backup_$TIMESTAMP.tar.gz"

mkdir -p $BACKUP_DIR

if [ -d "$WEB_DIR" ]; then
    tar -czf $BACKUP_DIR/$BACKUP_FILE -C /var/www html 2>/dev/null
    
    if [ $? -eq 0 ]; then
        echo "[OK] Backup cree: $BACKUP_FILE"
        echo "     Taille: $(du -h $BACKUP_DIR/$BACKUP_FILE | cut -f1)"
        
        echo ""
        echo "-> Transfert vers le serveur de backup..."
        
        # Créer le répertoire distant si nécessaire
        sshpass -p "$BACKUP_PASSWORD" ssh -o StrictHostKeyChecking=no $BACKUP_USER@$BACKUP_SERVER "mkdir -p $BACKUP_REMOTE_DIR" 2>/dev/null
        
        # Transfert SCP avec sshpass
        sshpass -p "$BACKUP_PASSWORD" scp -o StrictHostKeyChecking=no $BACKUP_DIR/$BACKUP_FILE $BACKUP_USER@$BACKUP_SERVER:$BACKUP_REMOTE_DIR/
        
        if [ $? -eq 0 ]; then
            echo "[OK] Transfert reussi vers $BACKUP_SERVER:$BACKUP_REMOTE_DIR/"
            
            # Suppression du fichier local après transfert réussi
            rm -f $BACKUP_DIR/$BACKUP_FILE
            echo "[OK] Fichier local supprime"
        else
            echo "[ERREUR] Echec du transfert SCP"
            echo "[INFO] Le fichier reste disponible localement: $BACKUP_DIR/$BACKUP_FILE"
        fi
    else
        echo "[ERREUR] Echec de la creation de l'archive"
    fi
else
    echo "[WARN] Repertoire $WEB_DIR non trouve"
fi

echo ""
echo "=== Backup termine ==="
