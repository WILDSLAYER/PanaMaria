<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Usuario</title>
</head>
<body>
    <h2>Agregar Nuevo Usuario</h2>
    <form action="index.php?action=guardarUsuario" method="POST">
        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" required>

        <label for="email">Correo:</label>
        <input type="correo" name="correo" required>

        <label for="telefono">Teléfono:</label>
        <input type="text" name="telefono" required>

        <label for="direccion">Dirección:</label>
        <input type="text" name="direccion" required>

        <label for="id_rol">Rol:</label>
        <select name="id_rol" required>
            <option value="1">Administrador</option>
            <option value="2">Empleado</option>
        </select>

        <label for="password">Contraseña:</label>
        <input type="password" name="password" required>

        <button type="submit">Crear Usuario</button>
    </form>
</body>
</html>
