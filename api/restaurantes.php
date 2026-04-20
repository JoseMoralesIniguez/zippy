<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../controllers/RestauranteController.php';
$controller = new RestauranteController();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $controller->crear();
        break;
    case 'GET':
        $controller->listar();
        break;
    case 'PUT':
        $controller->actualizar();
        break;
    case 'PATCH':
        $controller->alternarEstado();
        break;
    default:
        http_response_code(405);
        break;
}
?>