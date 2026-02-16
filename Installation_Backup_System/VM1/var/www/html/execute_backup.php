<?php
// execute_backup.php
// À placer dans /var/www/html/execute_backup.php sur VM1

header('Content-Type: application/json');

// Récupérer l'index du serveur, la destination et la source
$server_index = isset($_POST['server_index']) ? intval($_POST['server_index']) : -1;
$destination = isset($_POST['destination']) ? $_POST['destination'] : '';
$source = isset($_POST['source']) ? $_POST['source'] : '/var/www/html';

$response = [
    'success' => false,
    'message' => '',
    'output' => ''
];

// Fichier de configuration des serveurs
$config_file = '/var/www/html/servers_config.json';

// Charger tous les serveurs depuis le fichier JSON
$servers = [];
if (file_exists($config_file)) {
    $json_data = file_get_contents($config_file);
    $servers = json_decode($json_data, true);
    
    if (!is_array($servers)) {
        $servers = [];
    }
}

// Si le fichier n'existe pas ou est vide, créer la configuration par défaut
if (empty($servers)) {
    $servers = [
        [
            'name' => 'Serveur Web',
            'ip' => '10.3.123.21',
            'user' => 'rt',
            'password' => 'alexandre'
        ]
    ];
    
    // Sauvegarder la configuration par défaut
    file_put_contents($config_file, json_encode($servers, JSON_PRETTY_PRINT));
}

// Vérifier que l'index du serveur est valide
if ($server_index < 0 || $server_index >= count($servers)) {
    $response['message'] = "Index de serveur invalide (index: $server_index, nombre de serveurs: " . count($servers) . ")";
    $response['debug'] = [
        'server_index' => $server_index,
        'servers_count' => count($servers),
        'servers' => $servers
    ];
    echo json_encode($response);
    exit;
}

$server = $servers[$server_index];

// Définir la destination par défaut si non spécifiée
if (empty($destination)) {
    $destination = '/home/rt/backups/' . strtolower(str_replace(' ', '_', $server['name']));
}

// Script bash intégré (sera exécuté à distance)
$backup_script = <<<'BASH'
#!/bin/bash
echo "=== Début du backup Serveur ==="
echo "Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
echo "Hostname: $(hostname)"
echo "IP: $(hostname -I | awk '{print $1}')"
echo ""

# Configuration du serveur de backup
BACKUP_SERVER="10.3.123.20"
BACKUP_USER="rt"
BACKUP_PASSWORD="alexandre"

# Utiliser le répertoire passé en variable d'environnement, sinon utiliser la valeur par défaut
if [ -z "$BACKUP_REMOTE_DIR" ]; then
    BACKUP_REMOTE_DIR="/home/rt/backups/serveur_web"
fi

# Utiliser le répertoire source passé en variable d'environnement, sinon utiliser la valeur par défaut
if [ -z "$BACKUP_SOURCE_DIR" ]; then
    BACKUP_SOURCE_DIR="/var/www/html"
fi

echo "-> Répertoire source: $BACKUP_SOURCE_DIR"
echo "-> Répertoire de destination: $BACKUP_REMOTE_DIR"

# Vérification de l'espace disque
echo "-> Vérification de l'espace disque..."
df -h / | tail -1

echo ""
echo "-> Backup des fichiers en cours..."

# Configuration du backup
BACKUP_DIR="/tmp/backups"
TIMESTAMP=$(date '+%Y%m%d_%H%M%S')
BACKUP_FILE="backup_$TIMESTAMP.tar.gz"

mkdir -p $BACKUP_DIR

if [ -d "$BACKUP_SOURCE_DIR" ]; then
    # Déterminer le répertoire parent et le nom du dossier à sauvegarder
    PARENT_DIR=$(dirname "$BACKUP_SOURCE_DIR")
    DIR_NAME=$(basename "$BACKUP_SOURCE_DIR")
    
    tar -czf $BACKUP_DIR/$BACKUP_FILE -C "$PARENT_DIR" "$DIR_NAME" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        echo "[OK] Backup créé: $BACKUP_FILE"
        echo "     Source: $BACKUP_SOURCE_DIR"
        echo "     Taille: $(du -h $BACKUP_DIR/$BACKUP_FILE | cut -f1)"
        
        echo ""
        echo "-> Transfert vers le serveur de backup..."
        
        # Créer le répertoire distant si nécessaire
        sshpass -p "$BACKUP_PASSWORD" ssh -o StrictHostKeyChecking=no $BACKUP_USER@$BACKUP_SERVER "mkdir -p $BACKUP_REMOTE_DIR" 2>/dev/null
        
        if [ $? -eq 0 ]; then
            echo "[OK] Répertoire distant créé/vérifié"
        else
            echo "[WARN] Impossible de créer le répertoire distant"
        fi
        
        # Transfert SCP avec sshpass
        sshpass -p "$BACKUP_PASSWORD" scp -o StrictHostKeyChecking=no $BACKUP_DIR/$BACKUP_FILE $BACKUP_USER@$BACKUP_SERVER:$BACKUP_REMOTE_DIR/
        
        if [ $? -eq 0 ]; then
            echo "[OK] Transfert réussi vers $BACKUP_SERVER:$BACKUP_REMOTE_DIR/"
            
            # Suppression du fichier local après transfert réussi
            rm -f $BACKUP_DIR/$BACKUP_FILE
            echo "[OK] Fichier local supprimé"
            
            # Optionnel: Nettoyage des anciennes sauvegardes sur le serveur distant
            # Garder seulement les 10 dernières sauvegardes
            echo ""
            echo "-> Nettoyage des anciennes sauvegardes..."
            sshpass -p "$BACKUP_PASSWORD" ssh -o StrictHostKeyChecking=no $BACKUP_USER@$BACKUP_SERVER \
                "cd $BACKUP_REMOTE_DIR && ls -t backup_*.tar.gz 2>/dev/null | tail -n +11 | xargs -r rm -f" 2>/dev/null
            
            if [ $? -eq 0 ]; then
                echo "[OK] Anciennes sauvegardes nettoyées (conservées: 10 dernières)"
            fi
        else
            echo "[ERREUR] Échec du transfert SCP"
            echo "[INFO] Le fichier reste disponible localement: $BACKUP_DIR/$BACKUP_FILE"
        fi
    else
        echo "[ERREUR] Échec de la création de l'archive"
    fi
else
    echo "[WARN] Répertoire $BACKUP_SOURCE_DIR non trouvé"
fi

echo ""
echo "=== Backup terminé ==="
BASH;

// Échapper le script pour l'exécution SSH
$escaped_script = base64_encode($backup_script);

// Construire la commande SSH qui exécute le script directement
$command = sprintf(
    'sshpass -p %s ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 %s@%s "BACKUP_REMOTE_DIR=%s BACKUP_SOURCE_DIR=%s bash -c \\"echo %s | base64 -d | bash\\"" 2>&1',
    escapeshellarg($server['password']),
    escapeshellarg($server['user']),
    escapeshellarg($server['ip']),
    escapeshellarg($destination),
    escapeshellarg($source),
    escapeshellarg($escaped_script)
);

// Exécuter la commande
exec($command, $output, $return_code);

if ($return_code === 0) {
    $response['success'] = true;
    $response['message'] = sprintf('Backup de "%s" lancé avec succès', $server['name']);
    $response['output'] = implode("\n", $output);
} else {
    $response['success'] = false;
    $response['message'] = sprintf('Erreur lors de l\'exécution du backup de "%s"', $server['name']);
    $response['output'] = implode("\n", $output);
}

echo json_encode($response);
?>
