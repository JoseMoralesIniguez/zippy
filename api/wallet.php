<?php
// Mostrar errores (quitar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Encabezados CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../controllers/TransaccionWalletController.php';
$controller = new TransaccionWalletController();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Registrar un movimiento (Ingreso o Egreso)
        $controller->crear();
        break;
    
    case 'GET':
        // Si la URL pide "saldo", devolvemos el saldo total.
        // Si no, devolvemos el historial completo.
        $accion = isset($_GET['accion']) ? $_GET['accion'] : 'historial';
        
        if ($accion === 'saldo') {
            $controller->miSaldo();
        } else {
            $controller->miHistorial();
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "mensaje" => "Método HTTP no soportado."]);
        break;
}
?>