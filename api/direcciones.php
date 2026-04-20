<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once '../controllers/DireccionController.php';
$controller = new DireccionController();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST': $controller->crear(); break;
    case 'GET': $controller->listar(); break;
    default: http_response_code(405); break;
}