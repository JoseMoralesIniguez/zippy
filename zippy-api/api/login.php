<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../controllers/AuthController.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $controller = new AuthController();
    $controller->login();
} else {
    http_response_code(405);
    echo json_encode(array("status" => "error", "mensaje" => "Solo se permite el método POST."));
}
?>