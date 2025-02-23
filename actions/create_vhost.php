<?php
header('Content-Type: application/json'); // Indicar que la respuesta es JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serverName = $_POST['serverName'];
    $documentRoot = $_POST['documentRoot'];

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
        exec("sudo a2ensite {$serverName}.conf");
        exec("sudo systemctl reload apache2");

        // Respuesta de éxito
        echo json_encode([
            'status' => 'success',
            'message' => 'VirtualHost creado correctamente.',
            'redirect' => '/index.php?message=VirtualHost creado correctamente.&type=success'
        ]);
    } else {
        // Respuesta de error
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al crear el VirtualHost.'
        ]);
    }
    exit;
}
?>