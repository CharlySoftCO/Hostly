<?php
// edit.php
$serverName = $_GET['name'];
$configFilePath = "/etc/apache2/sites-available/{$serverName}.conf";

// Verificar si el archivo de configuración existe
if (!file_exists($configFilePath)) {
    // Redirigir a index.php con un mensaje de error
    header("Location: /index.php?message=El VirtualHost no existe.&type=error");
    exit;
}

// Leer el contenido del archivo de configuración
$vhostContent = file_get_contents($configFilePath);

// Extraer DocumentRoot usando una expresión regular
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
            <input type="hidden" name="oldServerName" value="<?= htmlspecialchars($serverName) ?>">
            <div class="mb-3">
                <label for="serverName" class="form-label">Nombre del Proyecto (ServerName)</label>
                <input type="text" class="form-control" id="serverName" name="serverName" value="<?= htmlspecialchars($serverName) ?>" required>
            </div>
            <div class="mb-3">
                <label for="documentRoot" class="form-label">Ruta del Proyecto (DocumentRoot)</label>
                <input type="text" class="form-control" id="documentRoot" name="documentRoot" value="<?= htmlspecialchars($documentRoot) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Manejar el envío del formulario -->
    <script>
        document.querySelector('form').addEventListener('submit', async (e) => {
            e.preventDefault(); // Evitar el envío tradicional del formulario

            const formData = new FormData(e.target);

            try {
                const response = await fetch(e.target.action, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Error en la solicitud.');
                }

                const result = await response.json();

                if (result.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: result.message,
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        if (result.redirect) {
                            window.location.href = result.redirect;
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Hubo un problema al procesar la solicitud.'
                });
                console.error(error);
            }
        });
    </script>
</body>

</html>