#!/bin/bash

echo "=== Configuration de VM2 ==="
echo ""

if [ "$EUID" -ne 0 ]; then 
    echo "[ERREUR] Ce script doit etre execute en tant que root"
    echo "Utilisez: sudo bash setup_vm2_user.sh"
    exit 1
fi

BACKUP_USER="rt"
BACKUP_PASSWORD="rt"
SCRIPTS_DIR="/opt/scripts"

echo "[1/4] Creation de l'utilisateur $BACKUP_USER..."
if id "$BACKUP_USER" >/dev/null 2>&1; then
    echo "      Utilisateur existe deja"
else
    useradd -m -s /bin/bash $BACKUP_USER
    echo "$BACKUP_USER:$BACKUP_PASSWORD" | chpasswd
    echo "      [OK] Utilisateur cree avec mot de passe"
fi

echo ""
echo "[2/4] Creation du repertoire des scripts..."
mkdir -p $SCRIPTS_DIR
chown $BACKUP_USER:$BACKUP_USER $SCRIPTS_DIR
chmod 755 $SCRIPTS_DIR
echo "      [OK] Repertoire $SCRIPTS_DIR cree"

echo ""
echo "[3/4] Creation du script de backup..."
cat > $SCRIPTS_DIR/backup_web.sh << 'EOF'
#!/bin/bash
# Script de backup du Serveur Web (VM2)

echo "=== Debut du backup Serveur Web ==="
echo "Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
echo "Hostname: $(hostname)"
echo "IP: $(hostname -I | awk '{print $1}')"
echo ""

# Verification de l'espace disque
echo "-> Verification de l'espace disque..."
df -h / | tail -1

echo ""
echo "-> Backup des fichiers web en cours..."

# Backup du repertoire web
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
EOF

chmod +x $SCRIPTS_DIR/backup_web.sh
chown $BACKUP_USER:$BACKUP_USER $SCRIPTS_DIR/backup_web.sh
echo "      [OK] Script de backup cree"
echo ""
echo "[4/4] Verification de la configuration SSH..."
if grep -q "^PasswordAuthentication yes" /etc/ssh/sshd_config; then
    echo "      [OK] Authentification par mot de passe activee"
else
    echo "      [INFO] Activation de l'authentification par mot de passe..."
    sed -i 's/^#*PasswordAuthentication.*/PasswordAuthentication yes/' /etc/ssh/sshd_config
    if ! grep -q "^PasswordAuthentication yes" /etc/ssh/sshd_config; then
        echo "PasswordAuthentication yes" >> /etc/ssh/sshd_config
    fi
    systemctl restart ssh
    echo "      [OK] SSH reconfigure"
fi

echo ""
echo "=========================================="
echo "[OK] Configuration VM2 terminee !"
echo "=========================================="
echo ""
echo "Utilisateur cree: $BACKUP_USER"
echo "Mot de passe: $BACKUP_PASSWORD"
echo "Script de backup: $SCRIPTS_DIR/backup_web.sh"
echo ""
echo "Testez depuis VM1:"
echo "  sshpass -p 'rt' ssh rt@10.3.123.21 'bash /opt/scripts/backup_web.sh'"
echo ""