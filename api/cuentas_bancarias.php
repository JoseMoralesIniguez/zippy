<?php
// Mostrar errores (quitar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Encabezados CORS y tipo de contenido
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Si es una petición de tipo OPTIONS (pre-flight de CORS generada por el navegador), terminamos con éxito rápido
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir el controlador de la cuenta bancaria
include_once '../controllers/CuentaBancariaController.php';

$controller = new CuentaBancariaController();
$method = $_SERVER['REQUEST_METHOD'];

// Enrutador básico basado en el método HTTP
switch ($method) {
    case 'POST':
        // Si mandan un POST, creamos una nueva cuenta
        $controller->crear();
        break;
    
    case 'GET':
        // Si mandan un GET, leemos las cuentas del usuario logueado
        $controller->listarPorUsuario();
        break;

    case 'DELETE':
        // Si mandan un DELETE, eliminamos la cuenta especificada
        $controller->eliminar();
        break;

    // Aquí podrías agregar case 'PUT': para el método actualizar() en el futuro

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["status" => "error", "mensaje" => "Método no soportado."]);
        break;
}
?>