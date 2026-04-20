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

// Incluir el controlador de finanzas
include_once '../controllers/FinanzasPedidoController.php';

$controller = new FinanzasPedidoController();
$method = $_SERVER['REQUEST_METHOD'];

// Enrutador básico basado en el método HTTP
switch ($method) {
    case 'POST':
        // Registrar las finanzas cuando un pedido se completa
        $controller->registrarFinanzas();
        break;
    
    case 'GET':
        // Ver el detalle financiero de un pedido específico
        $controller->verDetalleFinanciero();
        break;

    case 'PUT':
        // Procesar la liquidación (vincular el pedido a un pago de restaurante o repartidor)
        $controller->procesarLiquidacion();
        break;

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["status" => "error", "mensaje" => "Método HTTP no soportado en esta ruta. Usa POST, GET o PUT."]);
        break;
}
?>