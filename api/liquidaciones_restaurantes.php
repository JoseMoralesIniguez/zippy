<?php
// Mostrar errores (quitar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Encabezados CORS y tipo de contenido
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../controllers/LiquidacionRestauranteController.php';

$controller = new LiquidacionRestauranteController();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Generar un nuevo corte para el restaurante
        $controller->crear();
        break;
    
    case 'GET':
        // Obtener historial de liquidaciones de un restaurante
        $controller->listarPorRestaurante();
        break;

    case 'PUT':
        // Marcar liquidación como pagada
        $controller->registrarPago();
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "mensaje" => "Método HTTP no soportado."]);
        break;
}
?>