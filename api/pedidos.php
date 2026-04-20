<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../controllers/PedidoController.php';
$controller = new PedidoController();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $controller->crear();
        break;
    case 'GET':
        $controller->verPedido();
        break;
    case 'PUT':
        $accion = isset($_GET['accion']) ? $_GET['accion'] : '';
        if ($accion === 'aceptar') {
            $controller->aceptarPedido();
        } else if ($accion === 'pagar') {
            // AQUI ESTÁ LA NUEVA RUTA
            $controller->marcarPagado();
        } else {
            $controller->cambiarEstado();
        }
        break;
    default:
        http_response_code(405);
        break;
}
?>