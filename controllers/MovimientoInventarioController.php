<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/MovimientoInventario.php';
include_once '../models/RecetaProducto.php';
include_once '../models/Pedido.php';

class MovimientoInventarioController {
    private $db;
    private $movimientoModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->movimientoModel = new MovimientoInventario($this->db);
    }

    private function validarAcceso() {
        $headers = apache_request_headers();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            try {
                $decoded = JWT::decode($token, new Key($this->secreto_jwt, 'HS256'));
                return $decoded->data->id_usuario; 
            } catch (Exception $e) {
                http_response_code(401); exit();
            }
        }
        http_response_code(401); exit();
    }

    /**
     * REGISTRAR COMPRA O MERMA (POST)
     */
    public function registrarManual() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if(!empty($data->id_insumo) && !empty($data->tipo_movimiento) && !empty($data->cantidad)) {
            $this->movimientoModel->id_insumo = $data->id_insumo;
            $this->movimientoModel->tipo_movimiento = $data->tipo_movimiento; // 'Compra' o 'Merma'
            
            // Si es merma, la cantidad debe ser negativa
            $this->movimientoModel->cantidad = ($data->tipo_movimiento == 'Merma') ? -abs($data->cantidad) : abs($data->cantidad);
            $this->movimientoModel->nota = $data->nota;

            if($this->movimientoModel->registrar()) {
                echo json_encode(["status" => "success", "mensaje" => "Inventario actualizado."]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "mensaje" => "Error al procesar el movimiento."]);
            }
        }
    }

    /**
     * PROCESAR VENTA (La lógica que descuenta todo lo de un pedido)
     * Se llama cuando el pedido pasa a 'Entregado' o se paga en 'Mostrador'
     */
    public function procesarDescuentoPorVenta($id_pedido) {
        // 1. Obtener los productos del pedido (detalle_pedido)
        // 2. Por cada producto, buscar su RECETA
        // 3. Por cada ingrediente de la receta, registrar un movimiento de 'Venta' (cantidad negativa)
        
        // Esta función es interna y se dispara automáticamente desde el PedidoController.
    }
}
?>