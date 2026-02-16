<?php
// sync_servers.php
// API pour synchroniser les serveurs entre le frontend (localStorage) et le backend (JSON)

header('Content-Type: application/json');

$config_file = '/var/www/html/servers_config.json';
$action = isset($_POST['action']) ? $_POST['action'] : '';

$response = [
    'success' => false,
    'message' => '',
    'servers' => []
];

switch ($action) {
    case 'save':
        // Sauvegarder les serveurs envoyés par le frontend
        $servers_json = isset($_POST['servers']) ? $_POST['servers'] : '';
        
        if (empty($servers_json)) {
            $response['message'] = 'Aucune donnée de serveur fournie';
            break;
        }
        
        $servers = json_decode($servers_json, true);
        
        if (!is_array($servers)) {
            $response['message'] = 'Format de données invalide';
            break;
        }
        
        // Sauvegarder dans le fichier JSON
        if (file_put_contents($config_file, json_encode($servers, JSON_PRETTY_PRINT))) {
            $response['success'] = true;
            $response['message'] = 'Serveurs synchronisés avec succès';
            $response['servers'] = $servers;
        } else {
            $response['message'] = 'Erreur lors de l\'écriture du fichier de configuration';
        }
        break;
        
    case 'load':
        // Charger les serveurs depuis le fichier JSON
        if (file_exists($config_file)) {
            $content = file_get_contents($config_file);
            $servers = json_decode($content, true);
            
            if (is_array($servers)) {
                $response['success'] = true;
                $response['message'] = 'Serveurs chargés avec succès';
                $response['servers'] = $servers;
            } else {
                $response['message'] = 'Fichier de configuration corrompu';
            }
        } else {
            // Créer la configuration par défaut
            $default_servers = [
                [
                    'name' => 'Serveur Web',
                    'ip' => '10.3.123.21',
                    'port' => 22,
                    'user' => 'rt',
                    'password' => 'alexandre',
                    'editable' => false,
                    'lastBackup' => null,
                    'status' => 'ok'
                ]
            ];
            
            file_put_contents($config_file, json_encode($default_servers, JSON_PRETTY_PRINT));
            $response['success'] = true;
            $response['message'] = 'Configuration par défaut créée';
            $response['servers'] = $default_servers;
        }
        break;
        
    default:
        $response['message'] = 'Action non reconnue';
        break;
}

echo json_encode($response);
?>
