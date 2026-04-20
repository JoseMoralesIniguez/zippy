<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/Insumo.php';

class InsumoController {
    private $db;
    private $insumoModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->insumoModel = new Insumo($this->db);
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
     * 1. Registrar un nuevo insumo (POST)
     */
    public function crear() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_restaurante) && !empty($data->nombre) && !empty($data->unidad_medida)) {
            
            $this->insumoModel->id_restaurante = $data->id_restaurante;
            $this->insumoModel->nombre = $data->nombre;
            $this->insumoModel->unidad_medida = $data->unidad_medida;
            $this->insumoModel->costo_unitario = isset($data->costo_unitario) ? $data->costo_unitario : 0.00;
            $this->insumoModel->stock_actual = isset($data->stock_actual) ? $data->stock_actual : 0.0000;
            $this->insumoModel->stock_minimo = isset($data->stock_minimo) ? $data->stock_minimo : 0.0000;

            if ($this->insumoModel->crear()) {
                http_response_code(201);
                echo json_encode(["status" => "success", "mensaje" => "Insumo registrado correctamente en el inventario."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo registrar el insumo."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Datos incompletos. Faltan restaurante, nombre o unidad de medida."]);
        }
    }

    /**
     * 2. Listar insumos de un restaurante (GET)
     * Puede listar todo el inventario o solo las alertas de stock bajo.
     */
    public function listar() {
        $this->validarAcceso();
        $id_restaurante = isset($_GET['id_restaurante']) ? $_GET['id_restaurante'] : die();
        
        // Verificamos si la petición pide solo las alertas
        $solo_alertas = (isset($_GET['alertas']) && $_GET['alertas'] === 'true') ? true : false;

        if ($solo_alertas) {
            $stmt = $this->insumoModel->alertarStockBajo($id_restaurante);
        } else {
            $stmt = $this->insumoModel->obtenerPorRestaurante($id_restaurante);
        }
        
        $num = $stmt->rowCount();

        if ($num > 0) {
            $insumos_arr = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $item = [
                    "id_insumo" => (int)$row['id_insumo'],
                    "nombre" => $row['nombre'],
                    "unidad_medida" => $row['unidad_medida'],
                    "stock_actual" => (float)$row['stock_actual'],
                    "stock_minimo" => (float)$row['stock_minimo']
                ];
                
                // Si listamos todo, agregamos más detalles como el costo
                if (!$solo_alertas && isset($row['costo_unitario'])) {
                    $item["costo_unitario"] = (float)$row['costo_unitario'];
                }
                
                array_push($insumos_arr, $item);
            }
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $insumos_arr]);
        } else {
            $mensaje = $solo_alertas ? "No hay alertas de inventario. Todo está en orden." : "El inventario está vacío.";
            http_response_code(404);
            echo json_encode(["status" => "info", "mensaje" => $mensaje]);
        }
    }

    /**
     * 3. Actualizar información del insumo (PUT)
     * Solo para corregir datos base, no para meter o sacar mercancía.
     */
    public function actualizar() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_insumo) && !empty($data->nombre) && !empty($data->unidad_medida)) {
            
            $this->insumoModel->id_insumo = $data->id_insumo;
            $this->insumoModel->nombre = $data->nombre;
            $this->insumoModel->unidad_medida = $data->unidad_medida;
            $this->insumoModel->costo_unitario = isset($data->costo_unitario) ? $data->costo_unitario : 0.00;
            $this->insumoModel->stock_minimo = isset($data->stock_minimo) ? $data->stock_minimo : 0.0000;

            if ($this->insumoModel->actualizarInfo()) {
                http_response_code(200);
                echo json_encode(["status" => "success", "mensaje" => "Información del insumo actualizada."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo actualizar la información."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Datos incompletos para actualizar."]);
        }
    }
}
?>