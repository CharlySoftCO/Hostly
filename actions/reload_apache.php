<?php
// Recargar Apache
exec("sudo systemctl reload apache2", $output, $return_var);

if ($return_var === 0) {
    // Redirigir a index.php con un mensaje de éxito
    header("Location: /index.php?message=Apache recargado correctamente.&type=success");
} else {
    // Redirigir a index.php con un mensaje de error
    header("Location: /index.php?message=Error al recargar Apache.&type=error");
}
exit;
?>