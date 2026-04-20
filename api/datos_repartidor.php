<?php
// Mostrar errores (quitar en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Encabezados CORS y tipo de contenido
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Pre-flight de CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir el controlador
include_once '../controllers/DatosRepartidorController.php';

$controller = new DatosRepartidorController();
$method = $_SERVER['REQUEST_METHOD'];

// Enrutador principal
switch ($method) {
    case 'POST':
        // Crear el perfil por primera vez
        $controller->crear();
        break;
    
    case 'GET':
        // Obtener los datos del repartidor
        $controller->obtener();
        break;

    case 'PUT':
        // Como tenemos dos tipos de actualizaciones, buscamos el parámetro "accion" en la URL
        // Ejemplo: api/datos_repartidor.php?accion=ubicacion
        $accion = isset($_GET['accion']) ? $_GET['accion'] : '';

        if ($accion === 'perfil') {
            $controller->actualizarPerfil();
        } else if ($accion === 'ubicacion') {
            $controller->actualizarUbicacion();
        } else {
            http_response_code(400); // Bad Request
            echo json_encode([
                "status" => "error", 
                "mensaje" => "Falta definir la acción. Usa ?accion=perfil o ?accion=ubicacion en la URL."
            ]);
        }
        break;

    case 'DELETE':
        // Eliminar el perfil de repartidor
        $controller->eliminar();
        break;

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["status" => "error", "mensaje" => "Método HTTP no soportado."]);
        break;
}
?>