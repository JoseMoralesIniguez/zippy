<?php
// Configuración de cabeceras para permitir que aplicaciones móviles lean la API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../controllers/RestauranteController.php';

// Verificamos qué método HTTP se está usando
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    // Si es GET, instanciamos el controlador y pedimos la lista
    $controller = new RestauranteController();
    $controller->listarRestaurantesAbiertos();
} else {
    // Si intentan usar POST, PUT, DELETE en este endpoint específico
    http_response_code(405); // Método no permitido
    echo json_encode(array("mensaje" => "Método HTTP no soportado en esta ruta."));
}
?>