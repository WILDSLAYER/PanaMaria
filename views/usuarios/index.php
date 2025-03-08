<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Usuarios</title>
</head>
<body>
    <h2>Lista de Usuarios</h2>
    <a href="index.php?action=agregarUsuario">Agregar Usuario</a>
    <table border="1">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>correo</th>
                <th>Rol</th>
                <th>Fecha de Creación</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $usuario) : ?>
                <tr>
                    <td><?= $usuario['nombre'] ?></td>
                    <td><?= $usuario['correo'] ?></td>
                    <td><?= ucfirst($usuario['id_rol']) ?></td>
                    <td><?= $usuario['fecha_creacion'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
