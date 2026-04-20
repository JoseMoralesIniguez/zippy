<?php
// Mostrar errores (quitar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../controllers/ReclamoProductoController.php';
$controller = new ReclamoProductoController();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Cliente crea un reclamo
        $controller->crear();
        break;
    
    case 'GET':
        // Si mandan ID, vemos el detalle. Si no, vemos todos los pendientes.
        if (isset($_GET['id_reclamo'])) {
            $controller->verDetalle();
        } else {
            $controller->listarPendientes();
        }
        break;

    case 'PUT':
        // Soporte de Zippy resuelve (aprueba/rechaza) el reclamo
        $controller->resolver();
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "mensaje" => "Método HTTP no soportado."]);
        break;
}
?>