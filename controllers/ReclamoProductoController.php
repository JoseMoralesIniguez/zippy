<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/ReclamoProducto.php';

class ReclamoProductoController {
    private $db;
    private $reclamoModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->reclamoModel = new ReclamoProducto($this->db);
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
     * 1. Crear un nuevo reclamo (POST)
     * Utilizado por el cliente desde la app.
     */
    public function crear() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_detalle) && !empty($data->motivo) && !empty($data->comentario_cliente) && isset($data->monto_solicitado)) {
            
            $this->reclamoModel->id_detalle = $data->id_detalle;
            $this->reclamoModel->motivo = $data->motivo;
            $this->reclamoModel->comentario_cliente = $data->comentario_cliente;
            $this->reclamoModel->monto_solicitado = $data->monto_solicitado;
            $this->reclamoModel->url_evidencia_cliente = isset($data->url_evidencia_cliente) ? $data->url_evidencia_cliente : null;

            if ($this->reclamoModel->crear()) {
                http_response_code(201);
                echo json_encode(["status" => "success", "mensaje" => "Tu reclamo ha sido enviado. Lo revisaremos pronto."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo enviar el reclamo. Intenta de nuevo."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Datos incompletos. Se requiere id_detalle, motivo, comentario y monto."]);
        }
    }

    /**
     * 2. Obtener la Bandeja de Reclamos Pendientes (GET)
     * Utilizado por el panel de administración de Zyppy.
     */
    public function listarPendientes() {
        $this->validarAcceso(); // Aquí podrías validar que el rol sea 'Admin'
        
        $stmt = $this->reclamoModel->obtenerReclamosPendientes();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $reclamos_arr = array();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                array_push($reclamos_arr, $row);
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $reclamos_arr]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "info", "mensaje" => "¡Excelente! No hay reclamos pendientes por resolver."]);
        }
    }

    /**
     * 3. Ver el detalle de un solo reclamo (GET)
     */
    public function verDetalle() {
        $this->validarAcceso();
        $id_reclamo = isset($_GET['id_reclamo']) ? $_GET['id_reclamo'] : die();

        $this->reclamoModel->id_reclamo = $id_reclamo;
        if ($this->reclamoModel->obtenerPorId()) {
            $data = [
                "id_reclamo" => $this->reclamoModel->id_reclamo,
                "id_detalle" => $this->reclamoModel->id_detalle,
                "motivo" => $this->reclamoModel->motivo,
                "comentario_cliente" => $this->reclamoModel->comentario_cliente,
                "monto_solicitado" => $this->reclamoModel->monto_solicitado,
                "evidencia" => $this->reclamoModel->url_evidencia_cliente,
                "estado" => $this->reclamoModel->estado_resolucion,
                "fecha" => $this->reclamoModel->fecha_reclamo
            ];
            echo json_encode(["status" => "success", "data" => $data]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "mensaje" => "Reclamo no encontrado."]);
        }
    }

    /**
     * 4. Resolver un Reclamo (PUT)
     * Usado por el Admin para aprobar y reembolsar, o para rechazar la queja.
     */
    public function resolver() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_reclamo) && !empty($data->estado_resolucion)) {
            
            // Primero validamos que el reclamo exista y siga pendiente
            $this->reclamoModel->id_reclamo = $data->id_reclamo;
            
            if ($this->reclamoModel->obtenerPorId()) {
                if ($this->reclamoModel->estado_resolucion !== 'Pendiente') {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "mensaje" => "Este reclamo ya fue resuelto anteriormente."]);
                    return;
                }

                $this->reclamoModel->estado_resolucion = $data->estado_resolucion; // 'Aprobado' o 'Rechazado'
                
                // Si es aprobado, registramos cuánto le devolvimos y por dónde
                if ($data->estado_resolucion === 'Aprobado') {
                    $this->reclamoModel->monto_reembolsado = isset($data->monto_reembolsado) ? $data->monto_reembolsado : $this->reclamoModel->monto_solicitado;
                    $this->reclamoModel->metodo_reembolso = isset($data->metodo_reembolso) ? $data->metodo_reembolso : 'Saldo Zippy';
                } else {
                    // Si es rechazado, el monto devuelto es 0
                    $this->reclamoModel->monto_reembolsado = 0.00;
                    $this->reclamoModel->metodo_reembolso = null;
                }

                if ($this->reclamoModel->resolverReclamo()) {
                    http_response_code(200);
                    echo json_encode(["status" => "success", "mensaje" => "Reclamo resuelto como: " . $data->estado_resolucion]);
                } else {
                    http_response_code(503);
                    echo json_encode(["status" => "error", "mensaje" => "No se pudo actualizar el reclamo."]);
                }
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "mensaje" => "Reclamo no encontrado."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Se requiere id_reclamo y estado_resolucion (Aprobado/Rechazado)."]);
        }
    }
}
?>