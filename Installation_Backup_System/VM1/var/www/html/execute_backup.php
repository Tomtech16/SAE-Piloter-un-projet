<?php

header('Content-Type: application/json');

$server = isset($_POST['server']) ? $_POST['server'] : '';

$vm2_ip = '10.3.123.21';
$vm2_user = 'rt';
$vm2_password = 'rt';

$response = [
    'success' => false,
    'message' => '',
    'output' => ''
];

if ($server === 'web') {
    $script_path = '/opt/scripts/backup_web.sh';
    
    $command = sprintf(
        'sshpass -p %s ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 %s@%s "bash %s" 2>&1',
        escapeshellarg($vm2_password),
        escapeshellarg($vm2_user),
        escapeshellarg($vm2_ip),
        escapeshellarg($script_path)
    );
    
    exec($command, $output, $return_code);
    
    if ($return_code === 0) {
        $response['success'] = true;
        $response['message'] = 'Backup du Serveur Web lance avec succes';
        $response['output'] = implode("\n", $output);
    } else {
        $response['success'] = false;
        $response['message'] = 'Erreur lors de l\'execution du backup';
        $response['output'] = implode("\n", $output);
    }
    
} elseif ($server === 'logs') {
    $response['message'] = 'Serveur Logs - A implementer';
    
} else {
    $response['message'] = 'Serveur non reconnu';
}

echo json_encode($response);
?>
