<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/FinanzasPedido.php';

class FinanzasPedidoController {
    private $db;
    private $finanzasModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->finanzasModel = new FinanzasPedido($this->db);
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
            echo json_encode(["status" => "error", "mensaje" => "No autorizado."]);
            exit();
        }
    }

    /**
     * 1. Registrar finanzas al completar un pedido (POST)
     * Realiza los cálculos financieros automáticamente.
     */
    public function registrarFinanzas() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_pedido) && !empty($data->subtotal_comida) && !empty($data->tarifa_envio_cobrada)) {
            
            // --- Lógica de Negocio de Zyppy ---
            $subtotal = (float)$data->subtotal_comida;
            $tarifa_envio = (float)$data->tarifa_envio_cobrada;
            
            // Supongamos una comisión del 15% para la plataforma (configurable)
            $porcentaje = 15.00; 
            
            // 1. Calculamos cuánto nos quedamos del restaurante
            $comision_monto = $subtotal * ($porcentaje / 100);
            
            // 2. Lo que le queda al restaurante (Subtotal - Comisión)
            $pago_restaurante = $subtotal - $comision_monto;
            
            // 3. Lo que le queda al repartidor (Toda la tarifa de envío en este ejemplo)
            $pago_repartidor = $tarifa_envio; 
            
            // 4. Ganancia neta para Zyppy (La comisión del restaurante)
            $ganancia_zippy = $comision_monto;

            // Mapeo al modelo
            $this->finanzasModel->id_pedido = $data->id_pedido;
            $this->finanzasModel->subtotal_comida = $subtotal;
            $this->finanzasModel->tarifa_envio_cobrada = $tarifa_envio;
            $this->finanzasModel->porcentaje_aplicado = $porcentaje;
            $this->finanzasModel->comision_restaurante = $comision_monto;
            $this->finanzasModel->pago_neto_restaurante = $pago_restaurante;
            $this->finanzasModel->pago_neto_repartidor = $pago_repartidor;
            $this->finanzasModel->ganancia_neta_zippy = $ganancia_zippy;

            if ($this->finanzasModel->crear()) {
                http_response_code(201);
                echo json_encode([
                    "status" => "success", 
                    "mensaje" => "Finanzas del pedido calculadas y registradas.",
                    "detalles" => [
                        "pago_restaurante" => $pago_restaurante,
                        "pago_repartidor" => $pago_repartidor,
                        "ganancia_plataforma" => $ganancia_zippy
                    ]
                ]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "Error al registrar finanzas."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Datos incompletos para el cálculo."]);
        }
    }

    /**
     * 2. Consultar finanzas de un pedido (GET)
     */
    public function verDetalleFinanciero() {
        $this->validarAcceso();
        $id_pedido = isset($_GET['id_pedido']) ? $_GET['id_pedido'] : die();

        if ($this->finanzasModel->obtenerPorPedido($id_pedido)) {
            $res = [
                "id_finanza" => $this->finanzasModel->id_finanza,
                "id_pedido" => $this->finanzasModel->id_pedido,
                "subtotal" => $this->finanzasModel->subtotal_comida,
                "envio" => $this->finanzasModel->tarifa_envio_cobrada,
                "pago_restaurante" => $this->finanzasModel->pago_neto_restaurante,
                "pago_repartidor" => $this->finanzasModel->pago_neto_repartidor,
                "ganancia_neta" => $this->finanzasModel->ganancia_neta_zippy,
                "liquidado_restaurante" => $this->finanzasModel->id_liquidacion_restaurante ? true : false,
                "liquidado_repartidor" => $this->finanzasModel->id_liquidacion_repartidor ? true : false
            ];
            echo json_encode(["status" => "success", "data" => $res]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "mensaje" => "No hay registros financieros para este pedido."]);
        }
    }

    /**
     * 3. Procesar liquidación (PUT)
     * Vincula pedidos a un pago semanal para restaurante o repartidor.
     */
    public function procesarLiquidacion() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));
        $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : ''; // 'restaurante' o 'repartidor'

        if (!empty($data->id_pedido) && !empty($data->id_liquidacion)) {
            $this->finanzasModel->id_pedido = $data->id_pedido;
            
            if ($tipo === 'restaurante') {
                $this->finanzasModel->id_liquidacion_restaurante = $data->id_liquidacion;
                $success = $this->finanzasModel->liquidarRestaurante();
            } else if ($tipo === 'repartidor') {
                $this->finanzasModel->id_liquidacion_repartidor = $data->id_liquidacion;
                $success = $this->finanzasModel->liquidarRepartidor();
            } else {
                http_response_code(400);
                echo json_encode(["status" => "error", "mensaje" => "Tipo de liquidación no válido."]);
                return;
            }

            if ($success) {
                echo json_encode(["status" => "success", "mensaje" => "Liquidación vinculada correctamente."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "Error al procesar la liquidación."]);
            }
        }
    }
}
?>