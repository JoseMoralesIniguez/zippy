<?php
// Mostrar errores (quitar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Encabezados CORS y tipo de contenido
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Si es una petición de tipo OPTIONS (pre-flight de CORS), terminamos con éxito rápido
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir el controlador de liquidaciones
include_once '../controllers/LiquidacionRepartidorController.php';

$controller = new LiquidacionRepartidorController();
$method = $_SERVER['REQUEST_METHOD'];

// Enrutador básico basado en el método HTTP
switch ($method) {
    case 'POST':
        // Generar un nuevo corte (Crear deuda Pendiente)
        $controller->crear();
        break;
    
    case 'GET':
        // Obtener el historial de liquidaciones de un repartidor
        $controller->listarPorRepartidor();
        break;

    case 'PUT':
        // Marcar una liquidación como "Pagada" registrando la transferencia
        $controller->registrarPago();
        break;

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["status" => "error", "mensaje" => "Método HTTP no soportado en esta ruta. Usa POST, GET o PUT."]);
        break;
}
?>