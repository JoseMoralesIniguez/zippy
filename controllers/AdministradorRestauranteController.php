<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/AdministradorRestaurante.php';

class AdministradorRestauranteController {
    private $db;
    private $adminModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->adminModel = new AdministradorRestaurante($this->db);
    }

    /**
     * Función privada: Valida el Token y devuelve el ID del usuario logueado
     */
    private function obtenerIdUsuarioDesdeToken() {
        $headers = apache_request_headers();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            try {
                $decoded = JWT::decode($matches[1], new Key($this->secreto_jwt, 'HS256'));
                return $decoded->data->id_usuario;
            } catch (Exception $e) {
                http_response_code(401);
                echo json_encode(["status" => "error", "mensaje" => "Token inválido o expirado."]);
                exit();
            }
        }
        
        http_response_code(401);
        echo json_encode(["status" => "error", "mensaje" => "Se requiere Token de autorización."]);
        exit();
    }

    /**
     * POST: Asignar un nuevo usuario a un restaurante
     */
    public function asignar() {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_usuario_nuevo) && !empty($data->id_restaurante)) {
            // 1. Saber quién está haciendo la petición
            $mi_id_usuario = $this->obtenerIdUsuarioDesdeToken();

            // 2. Seguridad: Yo no puedo asignar a nadie si yo mismo no soy admin de ese lugar
            if (!$this->adminModel->verificarPermiso($mi_id_usuario, $data->id_restaurante)) {
                http_response_code(403);
                echo json_encode(["status" => "error", "mensaje" => "No tienes permiso para agregar administradores a esta sucursal."]);
                exit();
            }

            // 3. Proceder a asignar
            if ($this->adminModel->asignar($data->id_usuario_nuevo, $data->id_restaurante)) {
                http_response_code(201);
                echo json_encode(["status" => "success", "mensaje" => "Administrador asignado correctamente."]);
            } else {
                http_response_code(400); // Bad Request (probablemente ya estaba asignado)
                echo json_encode(["status" => "error", "mensaje" => "No se pudo asignar. Es posible que el usuario ya sea administrador de este restaurante."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Faltan datos: id_usuario_nuevo e id_restaurante."]);
        }
    }

    /**
     * GET: Obtener la lista de restaurantes que administra el usuario logueado
     * (Ideal para cuando el usuario abre la app y necesita ver sus negocios)
     */
    public function misRestaurantes() {
        $mi_id_usuario = $this->obtenerIdUsuarioDesdeToken();

        $stmt = $this->adminModel->obtenerRestaurantesDelUsuario($mi_id_usuario);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $restaurantes_arr = array();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Aquí adaptas los campos según cómo esté tu tabla de "restaurantes"
                $item = array(
                    "id_restaurante" => $row['id'], // o id_restaurante, según tu BD
                    "nombre" => $row['nombre_restaurante'] ?? 'Sin Nombre' // Cambia por tu columna real
                );
                array_push($restaurantes_arr, $item);
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $restaurantes_arr]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "info", "mensaje" => "No administras ningún restaurante actualmente."]);
        }
    }

    /**
     * DELETE: Remover el acceso de un usuario a un restaurante
     */
    public function remover() {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_usuario_remover) && !empty($data->id_restaurante)) {
            $mi_id_usuario = $this->obtenerIdUsuarioDesdeToken();

            // Seguridad
            if (!$this->adminModel->verificarPermiso($mi_id_usuario, $data->id_restaurante)) {
                http_response_code(403);
                echo json_encode(["status" => "error", "mensaje" => "No tienes permisos en este restaurante."]);
                exit();
            }

            if ($this->adminModel->remover($data->id_usuario_remover, $data->id_restaurante)) {
                http_response_code(200);
                echo json_encode(["status" => "success", "mensaje" => "Acceso removido correctamente."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo remover el acceso."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Faltan datos: id_usuario_remover e id_restaurante."]);
        }
    }
}
?>