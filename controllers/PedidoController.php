<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/Pedido.php';

class PedidoController {
    private $db;
    private $pedidoModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->pedidoModel = new Pedido($this->db);
    }

    /**
     * Valida el Token JWT y retorna el ID del usuario.
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
     * 1. Crear un pedido (POST)
     * Usado por el cliente para iniciar la orden.
     */
    public function crear() {
        $id_usuario_token = $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_restaurante) && !empty($data->total)) {
            
            $this->pedidoModel->id_cliente = $id_usuario_token; // El cliente es el dueño del token
            $this->pedidoModel->id_restaurante = $data->id_restaurante;
            $this->pedidoModel->subtotal = $data->subtotal;
            $this->pedidoModel->costo_envio = $data->costo_envio;
            $this->pedidoModel->total = $data->total;
            $this->pedidoModel->metodo_pago = $data->metodo_pago;
            $this->pedidoModel->direccion_entrega_lat = $data->direccion_entrega_lat;
            $this->pedidoModel->direccion_entrega_lng = $data->direccion_entrega_lng;
            $this->pedidoModel->estado_pedido = 'Pendiente';
            // ---> LA NUEVA LÍNEA PARA EL ORIGEN <---
            $this->pedidoModel->origen_pedido = isset($data->origen_pedido) ? $data->origen_pedido : 'App';

            if ($this->pedidoModel->crear()) {
                http_response_code(201);
                echo json_encode([
                    "status" => "success", 
                    "mensaje" => "Pedido creado exitosamente.",
                    "id_pedido" => $this->pedidoModel->id_pedido // Importante para insertar el detalle después
                ]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo crear el pedido."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Datos incompletos."]);
        }
    }

    /**
     * 2. Asignar un repartidor (PUT)
     * Se llama cuando un repartidor acepta el pedido en su app.
     */
    public function aceptarPedido() {
        $id_repartidor_token = $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_pedido)) {
            $this->pedidoModel->id_pedido = $data->id_pedido;
            $this->pedidoModel->id_repartidor = $id_repartidor_token;

            if ($this->pedidoModel->asignarRepartidor()) {
                echo json_encode(["status" => "success", "mensaje" => "Pedido asignado al repartidor."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo asignar el pedido."]);
            }
        }
    }

    /**
     * 3. Cambiar estado del pedido (PUT)
     * Usado por el restaurante o el repartidor para avanzar en el flujo.
     */
    public function cambiarEstado() {
    $this->validarAcceso();
    $data = json_decode(file_get_contents("php://input"));

    // Validamos que existan los datos necesarios
    if (!empty($data->id_pedido) && !empty($data->nuevo_estado)) {
        $this->pedidoModel->id_pedido = $data->id_pedido;
        $this->pedidoModel->estado_pedido = $data->nuevo_estado;

        if ($this->pedidoModel->actualizarEstado()) {
            // 1. Obtener el token del cliente (Asegúrate que id_cliente esté cargado en el modelo)
            $cliente_token = $this->usuarioModel->obtenerFCMToken($this->pedidoModel->id_cliente); 

            if ($cliente_token) {
                include_once '../services/NotificacionService.php';
                $notificacion = new NotificacionService();
            
                $nuevo_estado = $data->nuevo_estado; // Corregido: antes decía estado_pedido
                $mensaje = "";

                switch ($nuevo_estado) {
                    case 'Preparando':
                        $mensaje = "¡Tu orden de ¡Que Bonelessería! está siendo preparada! 👨‍🍳";
                        break;
                    case 'En Camino':
                        $mensaje = "¡Tu pedido va en camino! El repartidor llegará pronto. 🛵";
                        break;
                    case 'Entregado':
                        $mensaje = "¡Buen provecho! Tu pedido ha sido entregado. ✨";
                        break;
                }

                if ($mensaje != "") {
                    $notificacion->enviarNotificacion($cliente_token, "Actualización de tu pedido", $mensaje);
                }
            }

            // Respuesta de éxito
            http_response_code(200);
            echo json_encode(["status" => "success", "mensaje" => "Estado actualizado y notificación enviada."]);

        } else {
            http_response_code(503);
            echo json_encode(["status" => "error", "mensaje" => "No se pudo actualizar el estado en la base de datos."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "mensaje" => "Datos incompletos (id_pedido o nuevo_estado)."]);
    }
}

    /**
     * 4. Ver detalle de un pedido (GET)
     */
    public function verPedido() {
        $this->validarAcceso();
        $id_pedido = isset($_GET['id_pedido']) ? $_GET['id_pedido'] : die();

        $this->pedidoModel->id_pedido = $id_pedido;
        if ($this->pedidoModel->obtenerPorId()) {
            $pedido_data = [
                "id_pedido" => $this->pedidoModel->id_pedido,
                "estado" => $this->pedidoModel->estado_pedido,
                "total" => $this->pedidoModel->total,
                "id_repartidor" => $this->pedidoModel->id_repartidor,
                "metodo_pago" => $this->pedidoModel->metodo_pago,
                "fecha" => $this->pedidoModel->fecha_creacion
            ];
            echo json_encode(["status" => "success", "data" => $pedido_data]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "mensaje" => "Pedido no encontrado."]);
        }
    }
    /**
     * 5. Marcar Pedido como Pagado/Completado y Descontar Inventario (PUT)
     * Usado al cobrar en mostrador o cuando el repartidor entrega la comida.
     */
    public function marcarPagado() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_pedido)) {
            $this->pedidoModel->id_pedido = $data->id_pedido;
            $this->pedidoModel->estado_pedido = 'Completado'; // O 'Entregado'

            // 1. Cambiamos el estado del pedido
            if ($this->pedidoModel->actualizarEstado()) {
                
                // --- INICIO DE AUTOMATIZACIÓN DE INVENTARIO ---
                // Importamos los modelos necesarios para la magia
                include_once '../models/DetallePedido.php';
                include_once '../models/RecetaProducto.php';
                include_once '../models/MovimientoInventario.php';

                $detalleModel = new DetallePedido($this->db);
                $recetaModel = new RecetaProducto($this->db);
                $movimientoModel = new MovimientoInventario($this->db);

                // 2. Traemos todo lo que compró el cliente en este ticket
                // (Asumiendo que tienes un método obtenerPorPedido en tu DetallePedido)
                $productos_comprados = $detalleModel->obtenerPorPedido($data->id_pedido);

                while ($item = $productos_comprados->fetch(PDO::FETCH_ASSOC)) {
                    $id_producto = $item['id_producto'];
                    $cantidad_comprada = $item['cantidad']; // Ej: pidió 3 órdenes

                    // 3. Buscamos de qué está hecho este producto (su receta)
                    $ingredientes = $recetaModel->obtenerPorProducto($id_producto);

                    while ($ing = $ingredientes->fetch(PDO::FETCH_ASSOC)) {
                        // 4. Preparamos el descuento en el Kardex
                        $movimientoModel->id_insumo = $ing['id_insumo'];
                        $movimientoModel->tipo_movimiento = 'Venta';
                        
                        // La fórmula clave: (Cantidad en receta * Órdenes pedidas)
                        // Lo ponemos en NEGATIVO porque va a salir de nuestro almacén
                        $movimientoModel->cantidad = -($ing['cantidad_requerida'] * $cantidad_comprada);
                        
                        $movimientoModel->id_pedido_relacionado = $data->id_pedido;
                        $movimientoModel->nota = "Venta automatizada. Pedido #" . $data->id_pedido;

                        // 5. ¡PUM! Ejecutamos. Esto guarda el historial y descuenta el Insumo.
                        $movimientoModel->registrar();
                    }
                }
                // --- FIN DE AUTOMATIZACIÓN DE INVENTARIO ---

                http_response_code(200);
                echo json_encode(["status" => "success", "mensaje" => "Pedido cobrado. El inventario se ha descontado automáticamente."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo marcar el pedido como pagado."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Falta el id_pedido para procesar el cobro."]);
        }
    }
}
?>