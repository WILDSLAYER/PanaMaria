<?php
require_once __DIR__ . '/../config/database.php';

class UsuarioModel {
    private $db;

    public function __construct() {
        $this->db = Database::conectar();
    }

    public function crearUsuario($nombre, $email, $telefono, $direccion, $id_rol, $password) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (nombre, correo, telefono, direccion, id_rol, password, fecha_creacion) VALUES (:nombre, :correo, :telefono, :direccion, :id_rol, :password, :fecha_creacion)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre' => $nombre,
            ':correo' => $email,
            ':telefono' => $telefono,
            ':direccion' => $direccion,
            ':id_rol' => $id_rol,
            ':password' => $passwordHash,
            ':fecha_creacion' => date('Y-m-d H:i:s')
        ]);
    }

    public function obtenerUsuarios() {
        $sql = "SELECT id_usuario, nombre, correo, telefono, direccion, id_rol, password, fecha_creacion FROM usuarios";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
