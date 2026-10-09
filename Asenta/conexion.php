<?php
$host       = "localhost";
$usuario    = "root";
$password   = "";
$base_datos = "asentamientos";

$conexion = new mysqli($host, $usuario, $password, $base_datos);

if ($conexion->connect_error) {
    die("Error de conexion: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
?>