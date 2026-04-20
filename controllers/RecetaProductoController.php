<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/RecetaProducto.php';

class RecetaProductoController {
    private $db;
    private $recetaModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->recetaModel = new RecetaProducto($this->db);
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
     * 1. Agregar un ingrediente a la receta (POST)
     */
    public function crear() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_producto) && !empty($data->id_insumo) && isset($data->cantidad_requerida)) {
            
            $this->recetaModel->id_producto = $data->id_producto;
            $this->recetaModel->id_insumo = $data->id_insumo;
            $this->recetaModel->cantidad_requerida = $data->cantidad_requerida;

            if ($this->recetaModel->crear()) {
                http_response_code(201);
                echo json_encode(["status" => "success", "mensaje" => "Ingrediente agregado a la receta del producto."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo agregar el ingrediente. ¿Quizás ya estaba en la receta?"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Datos incompletos. Se requiere id_producto, id_insumo y cantidad_requerida."]);
        }
    }

    /**
     * 2. Ver la receta completa de un producto (GET)
     * Trae los nombres de los insumos y calcula cuánto cuesta preparar el platillo.
     */
    public function verReceta() {
        $this->validarAcceso();
        $id_producto = isset($_GET['id_producto']) ? $_GET['id_producto'] : die();

        $stmt = $this->recetaModel->obtenerPorProducto($id_producto);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $receta_arr = array();
            $costo_total_receta = 0; // Para calcular cuánto te cuesta el platillo
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cantidad = (float)$row['cantidad_requerida'];
                $costo_unitario = (float)$row['costo_unitario'];
                $costo_linea = $cantidad * $costo_unitario;
                
                $costo_total_receta += $costo_linea;

                array_push($receta_arr, [
                    "id_receta" => (int)$row['id_receta'],
                    "id_insumo" => (int)$row['id_insumo'],
                    "nombre_ingrediente" => $row['nombre_insumo'],
                    "cantidad_requerida" => $cantidad,
                    "unidad_medida" => $row['unidad_medida'],
                    "costo_ingrediente" => round($costo_linea, 2)
                ]);
            }

            http_response_code(200);
            echo json_encode([
                "status" => "success", 
                "data" => [
                    "ingredientes" => $receta_arr,
                    "costo_total_preparacion" => round($costo_total_receta, 2)
                ]
            ]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "info", "mensaje" => "Este producto aún no tiene una receta configurada."]);
        }
    }

    /**
     * 3. Actualizar la cantidad de un ingrediente (PUT)
     */
    public function actualizar() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_receta) && isset($data->cantidad_requerida)) {
            $this->recetaModel->id_receta = $data->id_receta;
            $this->recetaModel->cantidad_requerida = $data->cantidad_requerida;

            if ($this->recetaModel->actualizar()) {
                http_response_code(200);
                echo json_encode(["status" => "success", "mensaje" => "Cantidad actualizada correctamente."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo actualizar la cantidad."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Falta el id_receta o la nueva cantidad."]);
        }
    }

    /**
     * 4. Quitar un ingrediente de la receta (DELETE)
     */
    public function eliminar() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_receta)) {
            $this->recetaModel->id_receta = $data->id_receta;

            if ($this->recetaModel->eliminar()) {
                http_response_code(200);
                echo json_encode(["status" => "success", "mensaje" => "Ingrediente removido de la receta."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo remover el ingrediente."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Falta el id_receta."]);
        }
    }
}
?>