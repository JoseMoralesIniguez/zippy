<?php
// Cabeceras de seguridad y acceso para la API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../controllers/PedidoController.php';

$method = $_SERVER['REQUEST_METHOD'];

// Para crear un pedido, el método HTTP obligatorio es POST
if ($method == 'POST') {
    $controller = new PedidoController();
    $controller->registrarNuevoPedido();
} else {
    http_response_code(405);
    echo json_encode(array(
        "status" => "error", 
        "mensaje" => "Método no permitido. Use POST para crear pedidos."
    ));
}
?>