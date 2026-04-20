<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/Resena.php';

class ResenaController {
    private $db;
    private $resenaModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->resenaModel = new Resena($this->db);
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
                echo json_encode(["status" => "error", "mensaje" => "Token inválido."]);
                exit();
            }
        } else {
            http_response_code(401);
            echo json_encode(["status" => "error", "mensaje" => "Se requiere Token."]);
            exit();
        }
    }

    /**
     * 1. Crear una reseña (POST)
     */
    public function crear() {
        $id_cliente_token = $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_pedido)) {
            
            $this->resenaModel->id_pedido = $data->id_pedido;
            $this->resenaModel->id_cliente = $id_cliente_token;
            
            // Datos del restaurante
            $this->resenaModel->calificacion_restaurante = isset($data->calificacion_restaurante) ? $data->calificacion_restaurante : null;
            $this->resenaModel->comentario_restaurante = isset($data->comentario_restaurante) ? $data->comentario_restaurante : null;
            
            // Datos del repartidor
            $this->resenaModel->calificacion_repartidor = isset($data->calificacion_repartidor) ? $data->calificacion_repartidor : null;
            $this->resenaModel->comentario_repartidor = isset($data->comentario_repartidor) ? $data->comentario_repartidor : null;

            if ($this->resenaModel->crear()) {
                http_response_code(201);
                echo json_encode(["status" => "success", "mensaje" => "¡Gracias por tus comentarios! Ayudas a mejorar Zyppy."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo guardar la reseña. ¿Quizás ya calificaste este pedido?"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Falta el ID del pedido."]);
        }
    }

    /**
     * 2. Ver promedio de un restaurante (GET)
     * Útil para mostrar en la lista de restaurantes antes de que el cliente elija.
     */
    public function verReputacionRestaurante() {
        $id_restaurante = isset($_GET['id_restaurante']) ? $_GET['id_restaurante'] : die();

        $stats = $this->resenaModel->obtenerEstadisticasRestaurante($id_restaurante);

        if ($stats) {
            echo json_encode([
                "status" => "success", 
                "data" => [
                    "promedio" => round($stats['promedio_estrellas'], 1),
                    "total_votos" => (int)$stats['total_resenas']
                ]
            ]);
        } else {
            echo json_encode(["status" => "info", "mensaje" => "Este restaurante aún no tiene calificaciones."]);
        }
    }

    /**
     * 3. Ver la reseña de un pedido (GET)
     */
    public function verPorPedido() {
        $this->validarAcceso();
        $id_pedido = isset($_GET['id_pedido']) ? $_GET['id_pedido'] : die();

        $this->resenaModel->id_pedido = $id_pedido;
        if ($this->resenaModel->obtenerPorPedido()) {
            $data = [
                "calificacion_restaurante" => $this->resenaModel->calificacion_restaurante,
                "comentario_restaurante" => $this->resenaModel->comentario_restaurante,
                "calificacion_repartidor" => $this->resenaModel->calificacion_repartidor,
                "comentario_repartidor" => $this->resenaModel->comentario_repartidor,
                "fecha" => $this->resenaModel->fecha_resena
            ];
            echo json_encode(["status" => "success", "data" => $data]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "mensaje" => "Este pedido no ha sido calificado."]);
        }
    }
}
?>