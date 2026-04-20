<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../controllers/AuthController.php';

$auth = new AuthController();

// El registro siempre debe ser una petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth->registro();
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "mensaje" => "Método no permitido."]);
}
?>