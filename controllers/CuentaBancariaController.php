<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/CuentaBancaria.php';

class CuentaBancariaController {
    private $db;
    private $cuentaModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro"; // Mismo secreto

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->cuentaModel = new CuentaBancaria($this->db);
    }

    /**
     * Función auxiliar privada para validar el Token JWT.
     * Retorna el ID del usuario si el token es válido, o detiene la ejecución si hay error.
     */
    private function validarAcceso() {
        $headers = apache_request_headers();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            try {
                $decoded = JWT::decode($token, new Key($this->secreto_jwt, 'HS256'));
                // Retornamos directamente el ID del usuario dueño del token
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
     * 1. Crear una nueva cuenta bancaria (Requiere POST)
     */
    public function crear() {
        $data = json_decode(file_get_contents("php://input"));

        // Validamos que vengan los campos obligatorios
        if (!empty($data->banco) && !empty($data->numero_cuenta) && !empty($data->titular_cuenta)) {
            
            // Obtenemos el ID del usuario desde el Token (¡Seguridad primero!)
            $id_usuario_token = $this->validarAcceso();

            // Asignamos valores al modelo
            $this->cuentaModel->id_usuario = $id_usuario_token; // Forzamos el ID del token
            $this->cuentaModel->banco = $data->banco;
            $this->cuentaModel->numero_cuenta = $data->numero_cuenta;
            $this->cuentaModel->titular_cuenta = $data->titular_cuenta;
            
            // Campos opcionales / con valores por defecto
            $this->cuentaModel->tipo_cuenta = isset($data->tipo_cuenta) ? $data->tipo_cuenta : null;
            $this->cuentaModel->cuenta_principal = isset($data->cuenta_principal) ? $data->cuenta_principal : 0;
            $this->cuentaModel->token_pasarela = isset($data->token_pasarela) ? $data->token_pasarela : null;

            if ($this->cuentaModel->crear()) {
                http_response_code(201);
                echo json_encode(["status" => "success", "mensaje" => "Cuenta bancaria registrada correctamente."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo registrar la cuenta bancaria."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Datos incompletos. Se requiere banco, numero_cuenta y titular_cuenta."]);
        }
    }

    /**
     * 2. Obtener todas las cuentas del usuario logueado (Requiere GET)
     */
    public function listarPorUsuario() {
        // Obtenemos el ID del usuario desde el Token
        $id_usuario_token = $this->validarAcceso();

        $stmt = $this->cuentaModel->obtenerPorUsuario($id_usuario_token);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $cuentas_arr = array();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cuenta_item = array(
                    "id_cuenta" => $row['id_cuenta'],
                    "banco" => $row['banco'],
                    "cuenta_principal" => (bool)$row['cuenta_principal'],
                    "fecha_registro" => $row['fecha_registro'],
                    "numero_cuenta" => $row['numero_cuenta'],
                    "tipo_cuenta" => $row['tipo_cuenta'],
                    "titular_cuenta" => $row['titular_cuenta'],
                    "token_pasarela" => $row['token_pasarela']
                );
                array_push($cuentas_arr, $cuenta_item);
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $cuentas_arr]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "info", "mensaje" => "No tienes cuentas bancarias registradas."]);
        }
    }

    /**
     * 3. Eliminar una cuenta bancaria (Requiere DELETE)
     */
    public function eliminar() {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_cuenta)) {
            $id_usuario_token = $this->validarAcceso();

            $this->cuentaModel->id_cuenta = $data->id_cuenta;
            $this->cuentaModel->id_usuario = $id_usuario_token; // Seguridad: Confirmar propiedad

            if ($this->cuentaModel->eliminar()) {
                http_response_code(200);
                echo json_encode(["status" => "success", "mensaje" => "Cuenta bancaria eliminada."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo eliminar la cuenta. Verifica que te pertenezca."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Falta el ID de la cuenta a eliminar."]);
        }
    }
}
?>