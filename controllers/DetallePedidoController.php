<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/DetallePedido.php';

class DetallePedidoController {
    private $db;
    private $detalleModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro"; // Mismo secreto

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->detalleModel = new DetallePedido($this->db);
    }

    /**
     * Función auxiliar para validar el Token JWT.
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
     * 1. Agregar un producto a un pedido (Requiere POST)
     */
    public function crear() {
        $data = json_decode(file_get_contents("php://input"));

        // Validamos que el usuario esté autenticado
        $id_usuario_token = $this->validarAcceso();

        // Validamos que vengan los campos obligatorios
        if (!empty($data->id_pedido) && !empty($data->id_producto) && !empty($data->cantidad) && !empty($data->precio_unitario)) {
            
            $this->detalleModel->id_pedido = $data->id_pedido;
            $this->detalleModel->id_producto = $data->id_producto;
            $this->detalleModel->cantidad = $data->cantidad;
            $this->detalleModel->precio_unitario = $data->precio_unitario;
            
            // Instrucciones especiales es opcional
            $this->detalleModel->instrucciones_especiales = isset($data->instrucciones_especiales) ? $data->instrucciones_especiales : null;

            if ($this->detalleModel->crear()) {
                http_response_code(201);
                echo json_encode(["status" => "success", "mensaje" => "Producto agregado al pedido correctamente."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo agregar el producto al pedido."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Datos incompletos. Se requiere id_pedido, id_producto, cantidad y precio_unitario."]);
        }
    }

    /**
     * 2. Obtener todos los productos de un pedido (Requiere GET)
     */
    public function listarPorPedido() {
        // Validamos que el usuario esté autenticado
        $this->validarAcceso();

        $id_pedido = isset($_GET['id_pedido']) ? $_GET['id_pedido'] : die();

        if ($id_pedido) {
            $stmt = $this->detalleModel->obtenerPorPedido($id_pedido);
            $num = $stmt->rowCount();

            if ($num > 0) {
                $detalles_arr = array();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $detalle_item = array(
                        "id_detalle" => $row['id_detalle'],
                        "id_pedido" => $row['id_pedido'],
                        "id_producto" => $row['id_producto'],
                        "cantidad" => $row['cantidad'],
                        "precio_unitario" => $row['precio_unitario'],
                        "instrucciones_especiales" => $row['instrucciones_especiales']
                    );
                    array_push($detalles_arr, $detalle_item);
                }

                http_response_code(200);
                echo json_encode(["status" => "success", "data" => $detalles_arr]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "info", "mensaje" => "Este pedido no tiene productos asignados aún."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Se requiere el parámetro id_pedido en la URL."]);
        }
    }

    /**
     * 3. Eliminar un producto del pedido (Requiere DELETE)
     */
    public function eliminar() {
        $data = json_decode(file_get_contents("php://input"));
        
        // Validamos que el usuario esté autenticado
        $this->validarAcceso();

        if (!empty($data->id_detalle)) {
            $this->detalleModel->id_detalle = $data->id_detalle;

            if ($this->detalleModel->eliminar()) {
                http_response_code(200);
                echo json_encode(["status" => "success", "mensaje" => "Producto eliminado del pedido."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo eliminar el producto."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Falta el id_detalle a eliminar."]);
        }
    }
}
?>