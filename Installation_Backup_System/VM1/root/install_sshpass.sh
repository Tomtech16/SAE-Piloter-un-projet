#!/bin/bash
echo "=== Installation des dependances sur VM1 ==="
echo ""

if [ "$EUID" -ne 0 ]; then 
    echo "[ERREUR] Ce script doit etre execute en tant que root"
    echo "Utilisez: sudo bash install_requirements.sh"
    exit 1
fi

echo "[INFO] Mise a jour des paquets..."
apt-get update

echo ""
echo "[INFO] Installation de sshpass..."
apt-get install -y sshpass

if [ $? -eq 0 ]; then
    echo "[OK] sshpass installe avec succes"
else
    echo "[ERREUR] Erreur lors de l'installation de sshpass"
    exit 1
fi

echo ""
echo "[INFO] Verification de l'installation..."
which sshpass
sshpass -V

echo ""
echo "=========================================="
echo "[OK] Installation terminee"
echo "=========================================="