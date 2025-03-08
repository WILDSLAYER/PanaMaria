<?php
    $host = "localhost";
    $user = "root";
    $password = "";
    $database = "panaderia_maria";

    try{
        //conexion a la base de datos
        $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
        $conn = new PDO($dsn, $user, $password,[
            PDO::ATTR_ERRMODE =>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            ]);
        echo "Conexión exitosa";


    } catch (Exception $e){
        echo "Ocurrió un error con la base de datos: " . $e->getMessage();
    }
?>