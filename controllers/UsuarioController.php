<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/Usuario.php';

class UsuarioController {
    private $db;
    private $usuarioModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->usuarioModel = new Usuario($this->db);
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

    /**
     * Actualiza el Token de Firebase del usuario logueado
     */
    public function actualizarTokenFCM() {
        $id_usuario = $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->fcm_token)) {
            if ($this->usuarioModel->actualizarFCMToken($id_usuario, $data->fcm_token)) {
                http_response_code(200);
                echo json_encode(["status" => "success", "mensaje" => "Dispositivo vinculado para notificaciones."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo actualizar el token."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Falta el token de Firebase."]);
        }
    }
}