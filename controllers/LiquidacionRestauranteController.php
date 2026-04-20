<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/LiquidacionRestaurante.php';

class LiquidacionRestauranteController {
    private $db;
    private $liquidacionModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->liquidacionModel = new LiquidacionRestaurante($this->db);
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
     * 1. Generar un nuevo corte/liquidación para el restaurante (POST)
     */
    public function crear() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_restaurante) && !empty($data->monto_total) && !empty($data->fecha_inicio) && !empty($data->fecha_fin)) {
            
            $this->liquidacionModel->id_restaurante = $data->id_restaurante;
            $this->liquidacionModel->monto_total = $data->monto_total;
            $this->liquidacionModel->fecha_inicio = $data->fecha_inicio;
            $this->liquidacionModel->fecha_fin = $data->fecha_fin;
            
            // Forzamos el estatus a Pendiente
            $this->liquidacionModel->estatus = 'Pendiente'; 

            if ($this->liquidacionModel->crear()) {
                http_response_code(201);
                echo json_encode(["status" => "success", "mensaje" => "Liquidación generada con estatus Pendiente."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "Error al generar la liquidación del restaurante."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Faltan datos para generar el corte."]);
        }
    }

    /**
     * 2. Obtener el historial de liquidaciones de un restaurante (GET)
     */
    public function listarPorRestaurante() {
        $this->validarAcceso();
        $id_restaurante = isset($_GET['id_restaurante']) ? $_GET['id_restaurante'] : die();

        $stmt = $this->liquidacionModel->obtenerPorRestaurante($id_restaurante);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $liquidaciones_arr = array();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_push($liquidaciones_arr, $row);
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $liquidaciones_arr]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "info", "mensaje" => "No hay liquidaciones registradas para este restaurante."]);
        }
    }

    /**
     * 3. Marcar la liquidación del restaurante como Pagada (PUT)
     */
    public function registrarPago() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_liquidacion) && !empty($data->metodo_pago) && !empty($data->referencia_bancaria)) {
            
            $this->liquidacionModel->id_liquidacion = $data->id_liquidacion;
            
            if ($this->liquidacionModel->obtenerPorId()) {
                
                // Evitar doble pago
                if ($this->liquidacionModel->estatus === 'Pagado') {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "mensaje" => "Esta liquidación ya fue pagada anteriormente."]);
                    return;
                }

                $this->liquidacionModel->estatus = 'Pagado';
                $this->liquidacionModel->metodo_pago = $data->metodo_pago;
                $this->liquidacionModel->referencia_bancaria = $data->referencia_bancaria;

                if ($this->liquidacionModel->registrarPago()) {
                    http_response_code(200);
                    echo json_encode(["status" => "success", "mensaje" => "Pago al restaurante registrado exitosamente."]);
                } else {
                    http_response_code(503);
                    echo json_encode(["status" => "error", "mensaje" => "No se pudo registrar el pago."]);
                }
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "mensaje" => "La liquidación no existe."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Faltan datos. Se requiere id_liquidacion, metodo_pago y referencia_bancaria."]);
        }
    }
}
?>