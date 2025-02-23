<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que los campos no estén vacíos
    if (empty($_POST['oldServerName']) || empty($_POST['serverName']) || empty($_POST['documentRoot'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Todos los campos son obligatorios.'
        ]);
        exit;
    }

    $oldServerName = $_POST['oldServerName'];
    $serverName = $_POST['serverName'];
    $documentRoot = $_POST['documentRoot'];
    $oldDocumentRoot = "/var/www/" . $oldServerName;

    // Validar que la ruta del DocumentRoot sea válida
    if (!preg_match('/^\/var\/www\/[a-zA-Z0-9_-]+$/', $documentRoot)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'La ruta del proyecto no es válida. Debe comenzar con /var/www/.'
        ]);
        exit;
    }

    // Si el nombre del servidor cambió, renombrar la carpeta
    if ($oldServerName !== $serverName && file_exists($oldDocumentRoot)) {
        // Primero, asegurarse de que la carpeta de destino no exista
        if (file_exists($documentRoot)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'La carpeta de destino ya existe.'
            ]);
            exit;
        }

        // Intentar renombrar la carpeta
        exec("sudo mv {$oldDocumentRoot} {$documentRoot} 2>&1", $mvOutput, $mvReturnVar);
        if ($mvReturnVar !== 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al renombrar la carpeta del proyecto: ' . implode("\n", $mvOutput)
            ]);
            exit;
        }
    }

    // Crear el contenido del VirtualHost actualizado
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

    // Deshabilitar el sitio antiguo si el nombre cambió
    if ($oldServerName !== $serverName) {
        exec("sudo a2dissite {$oldServerName}.conf 2>&1", $disableOutput, $disableReturnVar);
        
        // Eliminar el archivo de configuración antiguo
        $oldConfigFilePath = "/etc/apache2/sites-available/{$oldServerName}.conf";
        if (file_exists($oldConfigFilePath)) {
            unlink($oldConfigFilePath);
        }
    }

    // Guardar el nuevo archivo de configuración
    $configFilePath = "/etc/apache2/sites-available/{$serverName}.conf";
    if (file_put_contents($configFilePath, $vhostContent)) {
        // Habilitar el nuevo sitio y recargar Apache
        exec("sudo a2ensite {$serverName}.conf", $a2ensiteOutput, $a2ensiteReturnVar);
        exec("sudo systemctl reload apache2", $reloadOutput, $reloadReturnVar);

        if ($a2ensiteReturnVar === 0 && $reloadReturnVar === 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'VirtualHost y carpeta actualizados correctamente.',
                'redirect' => '/index.php?message=VirtualHost y carpeta actualizados correctamente.&type=success'
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
            'message' => 'Error al actualizar el archivo de configuración del VirtualHost.'
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