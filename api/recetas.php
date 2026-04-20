<?php
// Mostrar errores (quitar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../controllers/RecetaProductoController.php';
$controller = new RecetaProductoController();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Agrega un insumo al producto
        $controller->crear();
        break;
    
    case 'GET':
        // Ver la receta completa de un platillo
        $controller->verReceta();
        break;

    case 'PUT':
        // Modificar la cantidad de un insumo en la receta
        $controller->actualizar();
        break;

    case 'DELETE':
        // Quitar un insumo de la receta
        $controller->eliminar();
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "mensaje" => "Método HTTP no soportado."]);
        break;
}
?>