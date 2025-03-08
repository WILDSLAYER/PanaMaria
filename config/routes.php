<?php
require_once __DIR__ . '/../controllers/UsuarioController.php';

$usuarioController = new UsuarioController();

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'usuarios':
            $usuarioController->index();
            break;
        case 'agregarUsuario':
            $usuarioController->agregar();
            break;
        case 'guardarUsuario':
            $usuarioController->guardar();
            break;
        default:
            echo "Página no encontrada.";
            break;
    }
} else {
    echo "<a href='index.php?action=usuarios'>Ver Usuarios</a>";
}
?>
