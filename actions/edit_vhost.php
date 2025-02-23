<?php
header('Content-Type: application/json'); // Indicar que la respuesta es JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldServerName = $_POST['oldServerName'];
    $serverName = $_POST['serverName'];
    $documentRoot = $_POST['documentRoot'];

    // Ruta del archivo de configuración antiguo
    $oldConfigFilePath = "/etc/apache2/sites-available/{$oldServerName}.conf";

    // Ruta del archivo de configuración nuevo
    $newConfigFilePath = "/etc/apache2/sites-available/{$serverName}.conf";

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

    // Eliminar el archivo de configuración antiguo
    if ($oldServerName !== $serverName) {
        exec("sudo a2dissite {$oldServerName}.conf");
        unlink($oldConfigFilePath);
    }

    // Guardar el nuevo archivo de configuración
    if (file_put_contents($newConfigFilePath, $vhostContent)) {
        // Habilitar el sitio y recargar Apache
        exec("sudo a2ensite {$serverName}.conf");
        exec("sudo systemctl reload apache2");

        // Respuesta de éxito
        echo json_encode([
            'status' => 'success',
            'message' => 'VirtualHost actualizado correctamente.',
            'redirect' => '/index.php?message=VirtualHost actualizado correctamente.&type=success'
        ]);
    } else {
        // Respuesta de error
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al actualizar el VirtualHost.'
        ]);
    }
    exit;
}
?>