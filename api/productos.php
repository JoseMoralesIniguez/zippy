<?php
// Mostrar errores (quitar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Encabezados CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../controllers/ProductoController.php';
$controller = new ProductoController();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Crear producto
        $controller->crear();
        break;
    
    case 'GET':
        // Listar productos de una categoría
        $controller->listarPorCategoria();
        break;

    case 'PUT':
        // Actualizar TODOS los datos de un producto
        $controller->actualizar();
        break;

    case 'PATCH':
        // Actualizar SOLO la disponibilidad (Activar/Desactivar)
        $controller->cambiarDisponibilidad();
        break;

    case 'DELETE':
        // Eliminar producto
        $controller->eliminar();
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "mensaje" => "Método HTTP no soportado."]);
        break;
}
?>