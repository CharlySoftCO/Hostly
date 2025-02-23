<?php
// edit.php
$serverName = $_GET['name'];
$configFilePath = "/etc/apache2/sites-available/{$serverName}.conf";
$vhostContent = file_get_contents($configFilePath);

// Extraer DocumentRoot
preg_match('/DocumentRoot\s+([^\s]+)/', $vhostContent, $matches);
$documentRoot = $matches[1] ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar VirtualHost</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Editar VirtualHost</h1>
        <form action="actions/edit_vhost.php" method="POST">
            <input type="hidden" name="oldServerName" value="<?= $serverName ?>">
            <div class="mb-3">
                <label for="serverName" class="form-label">Nombre del Proyecto (ServerName)</label>
                <input type="text" class="form-control" id="serverName" name="serverName" value="<?= $serverName ?>" required>
            </div>
            <div class="mb-3">
                <label for="documentRoot" class="form-label">Ruta del Proyecto (DocumentRoot)</label>
                <input type="text" class="form-control" id="documentRoot" name="documentRoot" value="<?= $documentRoot ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', async (e) => {
            e.preventDefault(); // Evitar el envío tradicional del formulario

            const formData = new FormData(e.target);
            const response = await fetch(e.target.action, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: result.message,
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    window.location.href = result.redirect;
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message
                });
            }
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>