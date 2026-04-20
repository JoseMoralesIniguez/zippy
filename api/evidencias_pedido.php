<?php
// Mostrar errores (quitar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Encabezados CORS y tipo de contenido
// Nota: Aunque respondemos en JSON, este endpoint acepta form-data en el POST
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Si es una petición de tipo OPTIONS (pre-flight de CORS), terminamos con éxito rápido
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir el controlador de evidencias
include_once '../controllers/EvidenciaPedidoController.php';

$controller = new EvidenciaPedidoController();
$method = $_SERVER['REQUEST_METHOD'];

// Enrutador básico basado en el método HTTP
switch ($method) {
    case 'POST':
        // Subir una nueva evidencia fotográfica
        $controller->crear();
        break;
    
    case 'GET':
        // Leer todas las fotos de un pedido
        $controller->listarPorPedido();
        break;

    case 'DELETE':
        // Eliminar una foto de la base de datos y del servidor
        $controller->eliminar();
        break;

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["status" => "error", "mensaje" => "Método HTTP no soportado en esta ruta."]);
        break;
}
?>