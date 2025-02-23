<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que los campos no estén vacíos
    if (empty($_POST['serverName']) || empty($_POST['documentRoot'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Todos los campos son obligatorios.'
        ]);
        exit;
    }

    $serverName = trim($_POST['serverName']);
    $documentRoot = trim($_POST['documentRoot']);

    // Validar que la ruta del DocumentRoot sea válida
    if (!preg_match('/^\/var\/www\/[a-zA-Z0-9_-]+$/', $documentRoot)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'La ruta del proyecto no es válida. Debe comenzar con /var/www/.'
        ]);
        exit;
    }

    // Crear la carpeta del proyecto si no existe
    if (!file_exists($documentRoot)) {
        if (!mkdir($documentRoot, 0755, true)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al crear la carpeta del proyecto.'
            ]);
            exit;
        }
    }

    // Crear el contenido del VirtualHost
    $vhostContent = "<VirtualHost *:80>\n";
    $vhostContent .= "    ServerName $serverName\n";
    $vhostContent .= "    DocumentRoot $documentRoot\n";
    $vhostContent .= "    <Directory $documentRoot>\n";
    $vhostContent .= "        AllowOverride All\n";
    $vhostContent .= "        Require all granted\n";
    $vhostContent .= "    </Directory>\n";
    $vhostContent .= "    ErrorLog \${APACHE_LOG_DIR}/{$serverName}_error.log\n";
    $vhostContent .= "    CustomLog \${APACHE_LOG_DIR}/{$serverName}_access.log combined\n";
    $vhostContent .= "</VirtualHost>";

    // Guardar el archivo de configuración
    $configFilePath = "/etc/apache2/sites-available/{$serverName}.conf";
    if (file_put_contents($configFilePath, $vhostContent)) {
        // Habilitar el sitio y recargar Apache
        exec("sudo a2ensite {$serverName}.conf", $a2ensiteOutput, $a2ensiteReturnVar);
        
        // Agregar entrada al archivo /etc/hosts usando echo y sudo
        $hostsEntry = "127.0.0.1\t{$serverName}";
        
        // Verificar si la entrada ya existe en /etc/hosts
        exec("sudo grep -q '^127\.0\.0\.1[[:space:]].*{$serverName}$' /etc/hosts", $grepOutput, $grepReturnVar);
        
        if ($grepReturnVar !== 0) {
            // La entrada no existe, agregarla
            exec("echo '{$hostsEntry}' | sudo tee -a /etc/hosts > /dev/null", $hostsOutput, $hostsReturnVar);
            
            if ($hostsReturnVar !== 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al agregar entrada en /etc/hosts.'
                ]);
                exit;
            }
        }

        // Recargar Apache
        exec("sudo systemctl reload apache2", $reloadOutput, $reloadReturnVar);

        if ($a2ensiteReturnVar === 0 && $reloadReturnVar === 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'VirtualHost creado correctamente y entrada en /etc/hosts agregada.',
                'redirect' => '/index.php?message=VirtualHost creado correctamente.&type=success'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al habilitar el VirtualHost o recargar Apache.'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al crear el archivo de configuración del VirtualHost.'
        ]);
    }
    exit;
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido.'
    ]);
    exit;
}
?>