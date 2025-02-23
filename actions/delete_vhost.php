<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serverName = trim($_POST['serverName']);
    $documentRoot = trim($_POST['documentRoot']);

    // Validar datos de entrada
    if (empty($serverName) || empty($documentRoot)) {
        header("Location: /index.php?message=Datos inválidos.&type=error");
        exit;
    }

    // Validar que la ruta sea dentro de /var/www
    if (!preg_match('/^\/var\/www\/[a-zA-Z0-9_-]+$/', $documentRoot)) {
        header("Location: /index.php?message=Ruta no válida.&type=error");
        exit;
    }

    $errors = [];
    
    // Deshabilitar el sitio
    exec("sudo a2dissite {$serverName}.conf 2>&1", $output, $return_var);
    if ($return_var !== 0) {
        $errors[] = "Error al deshabilitar el sitio: " . implode("\n", $output);
    }

    // Eliminar el archivo de configuración
    $configFilePath = "/etc/apache2/sites-available/{$serverName}.conf";
    if (file_exists($configFilePath)) {
        if (!unlink($configFilePath)) {
            $errors[] = "Error al eliminar el archivo de configuración";
        }
    }

    // Eliminar la carpeta del proyecto
    if (file_exists($documentRoot) && is_dir($documentRoot)) {
        // Primero intentamos cambiar los permisos
        exec("sudo chmod -R 777 {$documentRoot} 2>&1", $output, $return_var);
        
        // Luego intentamos eliminar
        exec("sudo rm -rf {$documentRoot} 2>&1", $output, $return_var);
        if ($return_var !== 0) {
            $errors[] = "Error al eliminar la carpeta: " . implode("\n", $output);
        }
    }

    // Eliminar entrada del archivo hosts usando un archivo temporal
    $tempFile = tempnam(sys_get_temp_dir(), 'hosts_');
    exec("sudo cp /etc/hosts {$tempFile}", $output, $return_var);
    exec("sudo chmod 666 {$tempFile}", $output, $return_var);
    
    $hostsContent = file_get_contents($tempFile);
    $newContent = preg_replace('/^127\.0\.0\.1\s+'.$serverName.'$/m', '', $hostsContent);
    file_put_contents($tempFile, $newContent);
    
    exec("sudo cp {$tempFile} /etc/hosts", $output, $return_var);
    if ($return_var !== 0) {
        $errors[] = "Error al actualizar el archivo hosts";
    }
    
    // Limpiar archivo temporal
    unlink($tempFile);

    // Recargar Apache solo si no hubo errores
    if (empty($errors)) {
        exec("sudo systemctl reload apache2 2>&1", $output, $return_var);
        if ($return_var === 0) {
            header("Location: /index.php?message=VirtualHost y entrada de hosts eliminados correctamente.&type=success");
            exit;
        } else {
            $errors[] = "Error al recargar Apache";
        }
    }

    // Si llegamos aquí, hubo errores
    $errorMessage = implode("; ", $errors);
    header("Location: /index.php?message=" . urlencode($errorMessage) . "&type=error");
    exit;
}
?>