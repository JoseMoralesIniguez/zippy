<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/TransaccionWallet.php';

class TransaccionWalletController {
    private $db;
    private $walletModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->walletModel = new TransaccionWallet($this->db);
    }

    /**
     * Valida el acceso mediante JWT y devuelve el ID del usuario.
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
     * 1. Crear una transacción (POST)
     * Puede ser un 'Ingreso' (recarga, reembolso) o 'Egreso' (pago de pedido).
     */
    public function crear() {
        // En un sistema real, un 'Ingreso' por recarga debería estar validado 
        // por un webhook de tu pasarela de pagos (Stripe, MercadoPago, etc).
        // Aquí asumimos que la petición interna ya está validada.
        
        $this->validarAcceso(); 
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_usuario) && !empty($data->tipo_movimiento) && !empty($data->monto)) {
            
            // Validar que el tipo de movimiento sea correcto
            if ($data->tipo_movimiento !== 'Ingreso' && $data->tipo_movimiento !== 'Egreso') {
                http_response_code(400);
                echo json_encode(["status" => "error", "mensaje" => "El tipo de movimiento debe ser 'Ingreso' o 'Egreso'."]);
                return;
            }

            // Si es un Egreso, primero debemos validar que tenga saldo suficiente
            if ($data->tipo_movimiento === 'Egreso') {
                $saldo_actual = $this->walletModel->calcularSaldo($data->id_usuario);
                if ($saldo_actual < $data->monto) {
                    http_response_code(402); // Payment Required
                    echo json_encode(["status" => "error", "mensaje" => "Saldo insuficiente en la Wallet para realizar esta operación."]);
                    return;
                }
            }

            $this->walletModel->id_usuario = $data->id_usuario;
            $this->walletModel->tipo_movimiento = $data->tipo_movimiento;
            $this->walletModel->monto = $data->monto;
            $this->walletModel->descripcion = isset($data->descripcion) ? $data->descripcion : null;
            $this->walletModel->id_pedido_relacionado = isset($data->id_pedido) ? $data->id_pedido : null;
            $this->walletModel->id_reclamo_relacionado = isset($data->id_reclamo) ? $data->id_reclamo : null;

            if ($this->walletModel->crear()) {
                http_response_code(201);
                echo json_encode(["status" => "success", "mensaje" => "Transacción de Wallet registrada correctamente."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo registrar el movimiento."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Faltan datos requeridos (id_usuario, tipo_movimiento, monto)."]);
        }
    }

    /**
     * 2. Obtener el historial de la Wallet del usuario (GET)
     */
    public function miHistorial() {
        $id_usuario_token = $this->validarAcceso();

        $stmt = $this->walletModel->obtenerHistorialUsuario($id_usuario_token);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $transacciones_arr = array();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_push($transacciones_arr, [
                    "id_transaccion" => $row['id_transaccion'],
                    "tipo" => $row['tipo_movimiento'],
                    "monto" => (float)$row['monto'],
                    "descripcion" => $row['descripcion'],
                    "fecha" => $row['fecha_transaccion']
                ]);
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $transacciones_arr]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "info", "mensaje" => "No tienes movimientos en tu Wallet aún."]);
        }
    }

    /**
     * 3. Consultar el Saldo Actual (GET)
     */
    public function miSaldo() {
        $id_usuario_token = $this->validarAcceso();

        $saldo = $this->walletModel->calcularSaldo($id_usuario_token);

        http_response_code(200);
        echo json_encode([
            "status" => "success", 
            "data" => [
                "saldo_disponible" => (float)$saldo
            ]
        ]);
    }
}
?>