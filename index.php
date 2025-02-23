<?php
// Ruta al archivo de configuración de Apache
$apacheConfigPath = '/etc/apache2/sites-available/';

// Obtener todos los archivos de configuración
$configFiles = glob($apacheConfigPath . '*.conf');

$virtualHosts = [];

// Archivos de configuración predeterminados que queremos excluir
$excludedFiles = ['000-default.conf', 'default-ssl.conf'];

// Leer cada archivo de configuración
foreach ($configFiles as $file) {
    $fileName = basename($file); // Obtener solo el nombre del archivo (ej: "proyecto1.local.conf")

    // Excluir archivos predeterminados
    if (in_array($fileName, $excludedFiles)) {
        continue;
    }

    $content = file_get_contents($file);
    // Buscar ServerName y DocumentRoot usando expresiones regulares
    preg_match_all('/ServerName\s+([^\s]+)/', $content, $serverNames);
    preg_match_all('/DocumentRoot\s+([^\s]+)/', $content, $documentRoots);

    // Combinar ServerName y DocumentRoot
    foreach ($serverNames[1] as $index => $serverName) {
        $virtualHosts[] = [
            'name' => $serverName,
            'path' => $documentRoots[1][$index] ?? 'No definido',
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de VirtualHost</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .card-img-top {
            height: 150px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Listado de VirtualHost</h1>
        <div class="text-center mb-4">
            <a href="create.php" class="btn btn-success">Crear Nuevo VirtualHost</a>
            <a href="actions/reload_apache.php" class="btn btn-info">Recargar Apache</a>
        </div>
        <div class="row">
            <?php if (!empty($virtualHosts)): ?>
                <?php foreach ($virtualHosts as $vhost): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow" onclick="window.location.href='http://<?= $vhost['name'] ?>'">
                            <img src="https://picsum.photos/300/150?random=<?= rand(1, 1000) ?>" class="card-img-top" alt="Imagen aleatoria">
                            <div class="card-body">
                                <h5 class="card-title"><?= $vhost['name'] ?></h5>
                                <p class="card-text"><?= $vhost['path'] ?></p>
                                <a href="edit.php?name=<?= $vhost['name'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                <form action="actions/delete_vhost.php" method="POST" style="display: inline;">
                                    <input type="hidden" name="serverName" value="<?= $vhost['name'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-warning text-center">
                        No se encontraron VirtualHost configurados.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Mostrar alertas -->
    <script>
        // Mostrar alerta si hay un mensaje en la URL
        const urlParams = new URLSearchParams(window.location.search);
        const message = urlParams.get('message');
        const type = urlParams.get('type');

        if (message && type) {
            Swal.fire({
                icon: type,
                title: message,
                showConfirmButton: false,
                timer: 2000 // Cerrar automáticamente después de 2 segundos
            }).then(() => {
                // Limpiar los parámetros de la URL después de mostrar la alerta
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }
    </script>
</body>
</html>