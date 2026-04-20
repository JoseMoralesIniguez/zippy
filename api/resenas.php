<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../controllers/ResenaController.php';
$controller = new ResenaController();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // El cliente envía su calificación
        $controller->crear();
        break;
    
    case 'GET':
        // Si mandan id_pedido, vemos esa reseña. 
        // Si mandan id_restaurante, vemos su promedio general.
        if (isset($_GET['id_pedido'])) {
            $controller->verPorPedido();
        } else if (isset($_GET['id_restaurante'])) {
            $controller->verReputacionRestaurante();
        }
        break;

    default:
        http_response_code(405);
        break;
}
?>