<?php
require_once __DIR__ . '/../models/UsuarioModel.php';

class UsuarioController {
    public function index() {
        $usuarioModel = new UsuarioModel();
        $usuarios = $usuarioModel->obtenerUsuarios();
        require_once __DIR__ . '/../views/usuarios/index.php';
    }

    public function agregar() {
        require_once __DIR__ . '/../views/usuarios/agregar.php';
    }

    public function guardar() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $nombre = $_POST["nombre"];
            $email = $_POST["correo"];
            $telefono = $_POST["telefono"];
            $direccion = $_POST["direccion"];
            $id_rol = $_POST["id_rol"];
            $password = $_POST["password"];

            $usuarioModel = new UsuarioModel();
            if ($usuarioModel->crearUsuario($nombre, $email, $telefono, $direccion, $id_rol, $password)) {
                header("Location: index.php?action=usuarios");
                exit();
            } else {
                header("Location: index.php?action=agregarUsuario&error=No se pudo crear el usuario");
                exit();
            }
        }
    }
}
?>
