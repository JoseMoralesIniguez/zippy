<?php
// Mostrar errores (quitar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Si es una petición de tipo OPTIONS (pre-flight de CORS), terminamos con éxito rápido
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../controllers/CategoriaController.php';

$controller = new CategoriaController();
$method = $_SERVER['REQUEST_METHOD'];

// Enrutador básico basado en el método HTTP
switch ($method) {
    case 'POST':
        // Si mandan un POST, queremos crear
        $controller->crear();
        break;
    
    case 'GET':
        // Si mandan un GET, queremos leer
        $controller->listarPorRestaurante();
        break;

    // Aquí podrías agregar case 'PUT': para actualizar y case 'DELETE': para borrar después

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["status" => "error", "mensaje" => "Método no soportado."]);
        break;
}
?>