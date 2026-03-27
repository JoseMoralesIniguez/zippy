<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Datos de prueba limpios
$nombre = "María Cliente";
$telefono = "5551234567";
$email = "maria@zippy.com";
$password_plana = "123456";

// Aquí PHP genera el Hash perfecto para tu versión exacta del servidor
$password_hash = password_hash($password_plana, PASSWORD_BCRYPT);

$query = "INSERT INTO Usuarios (nombre, telefono, email, password_hash, rol) 
          VALUES (:nombre, :telefono, :email, :hash, 'Cliente')";

$stmt = $db->prepare($query);
$stmt->bindParam(':nombre', $nombre);
$stmt->bindParam(':telefono', $telefono);
$stmt->bindParam(':email', $email);
$stmt->bindParam(':hash', $password_hash);

if($stmt->execute()) {
    echo json_encode(array(
        "status" => "success", 
        "mensaje" => "Usuario creado. Ahora ve a Postman e intenta iniciar sesión con maria@zippy.com y contraseña 123456"
    ));
} else {
    echo json_encode(array("status" => "error", "mensaje" => "No se pudo crear. Quizá el correo ya existe."));
}
?>