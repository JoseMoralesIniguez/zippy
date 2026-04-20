<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/DireccionUsuario.php';

class DireccionController {
    private $db;
    private $direccionModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro"; //

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->direccionModel = new DireccionUsuario($this->db);
    }

    private function validarAcceso() {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? '';
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            try {
                $decoded = JWT::decode($matches[1], new Key($this->secreto_jwt, 'HS256'));
                return $decoded->data->id_usuario;
            } catch (Exception $e) {
                http_response_code(401);
                exit(json_encode(["status" => "error", "mensaje" => "Token inválido."]));
            }
        }
        http_response_code(401);
        exit(json_encode(["status" => "error", "mensaje" => "No autorizado."]));
    }

    public function crear() {
        $id_usuario_token = $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->alias) && !empty($data->latitud)) {
            $this->direccionModel->id_usuario = $id_usuario_token;
            $this->direccionModel->alias = $data->alias;
            $this->direccionModel->direccion_completa = $data->direccion_completa;
            $this->direccionModel->referencias = $data->referencias ?? null;
            $this->direccionModel->latitud = $data->latitud;
            $this->direccionModel->longitud = $data->longitud;

            if ($this->direccionModel->crear()) {
                http_response_code(201);
                echo json_encode(["status" => "success", "mensaje" => "Dirección guardada."]);
            }
        }
    }

    public function listar() {
        $id_usuario_token = $this->validarAcceso();
        $stmt = $this->direccionModel->obtenerPorUsuario($id_usuario_token);
        $direcciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["status" => "success", "data" => $direcciones]);
    }
}