<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serverName = $_POST['serverName'];

    // Deshabilitar el sitio
    exec("sudo a2dissite {$serverName}.conf");

    // Eliminar el archivo de configuración
    $configFilePath = "/etc/apache2/sites-available/{$serverName}.conf";
    if (unlink($configFilePath)) {
        // Recargar Apache
        exec("sudo systemctl reload apache2");

        // Redirigir a index.php con un mensaje de éxito
        header("Location: /index.php?message=VirtualHost eliminado correctamente.&type=success");
    } else {
        // Redirigir a index.php con un mensaje de error
        header("Location: /index.php?message=Error al eliminar el VirtualHost.&type=error");
    }
    exit;
}
?>