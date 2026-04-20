<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/Categoria.php';
include_once '../models/AdministradorRestaurante.php';

class CategoriaController {
    private $db;
    private $categoriaModel;
    private $adminModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro"; // ¡El mismo del login!

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->categoriaModel = new Categoria($this->db);
        $this->adminModel = new AdministradorRestaurante($this->db);
    }

    /**
     * Función auxiliar privada para validar el Token y los permisos
     * Retorna el ID del usuario si todo está bien, o detiene la ejecución si hay error.
     */
    private function validarAcceso($id_restaurante) {
        // 1. Obtener los headers de la petición HTTP
        $headers = apache_request_headers();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

        // 2. Extraer el token (formato "Bearer eyJhbGciOi...")
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            try {
                // 3. Decodificar el token
                $decoded = JWT::decode($token, new Key($this->secreto_jwt, 'HS256'));
                $id_usuario = $decoded->data->id_usuario;

                // 4. Verificar si este usuario es administrador de este restaurante
                if (!$this->adminModel->verificarPermiso($id_usuario, $id_restaurante)) {
                    http_response_code(403); // Prohibido
                    echo json_encode(["status" => "error", "mensaje" => "No tienes permisos sobre este restaurante."]);
                    exit(); // Detenemos la ejecución
                }

                return $id_usuario; // Si pasa todo, devolvemos el ID

            } catch (Exception $e) {
                http_response_code(401); // No autorizado
                echo json_encode(["status" => "error", "mensaje" => "Token inválido o expirado."]);
                exit();
            }
        } else {
            http_response_code(401);
            echo json_encode(["status" => "error", "mensaje" => "Se requiere un Token de autorización."]);
            exit();
        }
    }

    /**
     * Crear una nueva categoría (Requiere POST)
     */
    public function crear() {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_restaurante) && !empty($data->nombre_categoria)) {
            
            // ¡Validamos seguridad antes de hacer cualquier cosa!
            $this->validarAcceso($data->id_restaurante);

            $this->categoriaModel->id_restaurante = $data->id_restaurante;
            $this->categoriaModel->nombre_categoria = $data->nombre_categoria;

            if ($this->categoriaModel->crear()) {
                http_response_code(201); // 201 Created
                echo json_encode(["status" => "success", "mensaje" => "Categoría creada correctamente."]);
            } else {
                http_response_code(503); // Service Unavailable
                echo json_encode(["status" => "error", "mensaje" => "No se pudo crear la categoría."]);
            }
        } else {
            http_response_code(400); // Bad Request
            echo json_encode(["status" => "error", "mensaje" => "Datos incompletos. Se requiere id_restaurante y nombre_categoria."]);
        }
    }

    /**
     * Obtener categorías de un restaurante (Requiere GET)
     */
    public function listarPorRestaurante() {
        // En peticiones GET, los datos suelen venir en la URL: api/categorias.php?id_restaurante=1
        $id_restaurante = isset($_GET['id_restaurante']) ? $_GET['id_restaurante'] : die();

        // Validamos seguridad (solo administradores pueden ver sus categorías en esta ruta)
        $this->validarAcceso($id_restaurante);

        $stmt = $this->categoriaModel->obtenerPorRestaurante($id_restaurante);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $categorias_arr = array();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categoria_item = array(
                    "id_categoria" => $row['id_categoria'],
                    "id_restaurante" => $row['id_restaurante'],
                    "nombre_categoria" => $row['nombre_categoria']
                );
                array_push($categorias_arr, $categoria_item);
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $categorias_arr]);
        } else {
            http_response_code(404); // Not Found
            echo json_encode(["status" => "info", "mensaje" => "No se encontraron categorías para este restaurante."]);
        }
    }
}
?>