<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/Restaurante.php';

class RestauranteController {
    private $db;
    private $restauranteModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->restauranteModel = new Restaurante($this->db);
    }

    /**
     * Valida el acceso mediante JWT.
     */
    private function validarAcceso() {
        $headers = apache_request_headers();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            try {
                $decoded = JWT::decode($token, new Key($this->secreto_jwt, 'HS256'));
                return $decoded->data->id_usuario; 
            } catch (Exception $e) {
                http_response_code(401);
                echo json_encode(["status" => "error", "mensaje" => "Token inválido o expirado."]);
                exit();
            }
        } else {
            http_response_code(401);
            echo json_encode(["status" => "error", "mensaje" => "Se requiere autorización."]);
            exit();
        }
    }

    /**
     * 1. Registrar un nuevo restaurante (POST)
     */
    public function crear() {
        $this->validarAcceso(); // Solo admins deberían crear restaurantes
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->nombre) && !empty($data->direccion) && isset($data->latitud) && isset($data->longitud)) {
            
            $this->restauranteModel->nombre = $data->nombre;
            $this->restauranteModel->direccion = $data->direccion;
            $this->restauranteModel->latitud = $data->latitud;
            $this->restauranteModel->longitud = $data->longitud;
            $this->restauranteModel->porcentaje_comision = isset($data->porcentaje_comision) ? $data->porcentaje_comision : 20.00;
            $this->restauranteModel->abierto = 1; // Nace abierto por defecto

            if ($this->restauranteModel->crear()) {
                http_response_code(201);
                echo json_encode(["status" => "success", "mensaje" => "Restaurante registrado con éxito."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo registrar el restaurante."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Datos incompletos. Nombre, dirección y coordenadas son requeridos."]);
        }
    }

    /**
     * 2. Listar restaurantes (GET)
     */
    public function listar() {
        // ¿Viene del panel de admin o de la app del cliente?
        $solo_abiertos = (isset($_GET['vista']) && $_GET['vista'] === 'admin') ? false : true;

        $stmt = $this->restauranteModel->obtenerTodos($solo_abiertos);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $restaurantes_arr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_push($restaurantes_arr, [
                    "id_restaurante" => (int)$row['id_restaurante'],
                    "nombre" => $row['nombre'],
                    "direccion" => $row['direccion'],
                    "latitud" => (float)$row['latitud'],
                    "longitud" => (float)$row['longitud'],
                    "abierto" => (bool)$row['abierto'],
                    "comision" => (float)$row['porcentaje_comision']
                ]);
            }
            echo json_encode(["status" => "success", "data" => $restaurantes_arr]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "info", "mensaje" => "No hay restaurantes disponibles."]);
        }
    }

    /**
     * 3. Actualizar perfil del restaurante (PUT)
     */
    public function actualizar() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_restaurante)) {
            $this->restauranteModel->id_restaurante = $data->id_restaurante;
            $this->restauranteModel->nombre = $data->nombre;
            $this->restauranteModel->direccion = $data->direccion;
            $this->restauranteModel->latitud = $data->latitud;
            $this->restauranteModel->longitud = $data->longitud;
            $this->restauranteModel->porcentaje_comision = $data->porcentaje_comision;

            if ($this->restauranteModel->actualizar()) {
                echo json_encode(["status" => "success", "mensaje" => "Información actualizada."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "Error al actualizar."]);
            }
        }
    }

    /**
     * 4. Cambiar estado Abierto/Cerrado (PATCH)
     */
    public function alternarEstado() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_restaurante) && isset($data->abierto)) {
            $this->restauranteModel->id_restaurante = $data->id_restaurante;
            $this->restauranteModel->abierto = $data->abierto ? 1 : 0;

            if ($this->restauranteModel->cambiarEstadoOperativo()) {
                $msg = $this->restauranteModel->abierto ? "Restaurante abierto" : "Restaurante cerrado";
                echo json_encode(["status" => "success", "mensaje" => $msg]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo cambiar el estado."]);
            }
        }
    }
}
?>