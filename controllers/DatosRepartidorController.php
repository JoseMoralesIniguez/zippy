<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/DatosRepartidor.php';

class DatosRepartidorController {
    private $db;
    private $repartidorModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro"; // Mismo secreto

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->repartidorModel = new DatosRepartidor($this->db);
    }

    /**
     * Función auxiliar para validar el Token JWT.
     * Retorna el ID del usuario si es válido, o detiene la ejecución si hay error.
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
            echo json_encode(["status" => "error", "mensaje" => "Se requiere un Token de autorización."]);
            exit();
        }
    }

    /**
     * 1. Crear el perfil de repartidor (Requiere POST)
     */
    public function crear() {
        $data = json_decode(file_get_contents("php://input"));

        // Validamos campos obligatorios (tipo de vehículo y estatus inicial)
        if (!empty($data->estatus_conexion) && !empty($data->tipo_vehiculo)) {
            
            $id_usuario_token = $this->validarAcceso();

            $this->repartidorModel->id_usuario = $id_usuario_token;
            $this->repartidorModel->estatus_conexion = $data->estatus_conexion;
            $this->repartidorModel->tipo_vehiculo = $data->tipo_vehiculo;
            
            // Ubicación inicial (puede ser nula si aún no activa el GPS)
            $this->repartidorModel->latitud_actual = isset($data->latitud_actual) ? $data->latitud_actual : null;
            $this->repartidorModel->longitud_actual = isset($data->longitud_actual) ? $data->longitud_actual : null;

            if ($this->repartidorModel->crear()) {
                http_response_code(201);
                echo json_encode(["status" => "success", "mensaje" => "Perfil de repartidor creado correctamente."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo crear el perfil. ¿Es posible que ya exista uno para este usuario?"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Datos incompletos. Se requiere estatus_conexion y tipo_vehiculo."]);
        }
    }

    /**
     * 2. Obtener los datos del repartidor (Requiere GET)
     */
    public function obtener() {
        $id_usuario_token = $this->validarAcceso();
        $this->repartidorModel->id_usuario = $id_usuario_token;

        if ($this->repartidorModel->obtenerPorId()) {
            $repartidor_arr = array(
                "id_usuario" => $this->repartidorModel->id_usuario,
                "estatus_conexion" => $this->repartidorModel->estatus_conexion,
                "latitud_actual" => $this->repartidorModel->latitud_actual,
                "longitud_actual" => $this->repartidorModel->longitud_actual,
                "tipo_vehiculo" => $this->repartidorModel->tipo_vehiculo
            );

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $repartidor_arr]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "info", "mensaje" => "No se encontró un perfil de repartidor para este usuario."]);
        }
    }

    /**
     * 3. Actualizar información general (Requiere PUT)
     * Usado por ejemplo cuando el repartidor cambia de bicicleta a moto en la app.
     */
    public function actualizarPerfil() {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->tipo_vehiculo)) {
            $id_usuario_token = $this->validarAcceso();

            $this->repartidorModel->id_usuario = $id_usuario_token;
            $this->repartidorModel->tipo_vehiculo = $data->tipo_vehiculo;

            if ($this->repartidorModel->actualizarPerfil()) {
                http_response_code(200);
                echo json_encode(["status" => "success", "mensaje" => "Perfil de repartidor actualizado."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo actualizar el perfil."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Se requiere el tipo_vehiculo para actualizar."]);
        }
    }

    /**
     * 4. Actualizar estado y ubicación del GPS (Requiere PUT)
     * Este endpoint será llamado constantemente por la app del repartidor en segundo plano.
     */
    public function actualizarUbicacion() {
        $data = json_decode(file_get_contents("php://input"));

        if (isset($data->estatus_conexion) && isset($data->latitud_actual) && isset($data->longitud_actual)) {
            $id_usuario_token = $this->validarAcceso();

            $this->repartidorModel->id_usuario = $id_usuario_token;
            $this->repartidorModel->estatus_conexion = $data->estatus_conexion;
            $this->repartidorModel->latitud_actual = $data->latitud_actual;
            $this->repartidorModel->longitud_actual = $data->longitud_actual;

            if ($this->repartidorModel->actualizarUbicacionYEstado()) {
                http_response_code(200);
                echo json_encode(["status" => "success", "mensaje" => "Ubicación y estado actualizados."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo registrar la ubicación."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Faltan coordenadas o estado de conexión."]);
        }
    }

    /**
     * 5. Eliminar el perfil de repartidor (Requiere DELETE)
     */
    public function eliminar() {
        $id_usuario_token = $this->validarAcceso();
        $this->repartidorModel->id_usuario = $id_usuario_token;

        if ($this->repartidorModel->eliminar()) {
            http_response_code(200);
            echo json_encode(["status" => "success", "mensaje" => "Perfil de repartidor eliminado correctamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["status" => "error", "mensaje" => "No se pudo eliminar el perfil."]);
        }
    }
}
?>