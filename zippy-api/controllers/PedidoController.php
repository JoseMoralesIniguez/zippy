<?php
include_once '../config/Database.php';
include_once '../models/Pedido.php';
include_once '../models/Producto.php'; // Agregamos el nuevo modelo

class PedidoController {
    private $db;
    private $pedidoModel;
    private $productoModel; // Nueva variable

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        $this->pedidoModel = new Pedido($this->db);
        $this->productoModel = new Producto($this->db); // Lo instanciamos
    }

    public function registrarNuevoPedido() {
        $data = json_decode(file_get_contents("php://input"));

        // Validamos que vengan los datos básicos
        if (!empty($data->id_cliente) && !empty($data->id_restaurante) && !empty($data->productos) && count($data->productos) > 0) {
            
            $subtotal_real_calculado = 0;
            $productos_validados = array();

            // =========================================================
            // CAPA DE SEGURIDAD: VERIFICACIÓN DE PRECIOS
            // =========================================================
            foreach ($data->productos as $item) {
                // Vamos a la base de datos a preguntar por este producto
                $info_db = $this->productoModel->obtenerPrecioYDisponibilidad($item->id_producto);

                // Si el producto existe en la DB y además está disponible
                if ($info_db && $info_db['disponible'] == 1) {
                    
                    // FORZAMOS el precio de la base de datos ignorando el de la App
                    $precio_seguro = $info_db['precio'];
                    $item->precio_unitario = $precio_seguro; 
                    
                    // Sumamos a nuestro subtotal matemático seguro
                    $subtotal_real_calculado += ($precio_seguro * $item->cantidad);
                    
                    // Metemos el producto ya verificado a un nuevo arreglo limpio
                    array_push($productos_validados, $item);

                } else {
                    // Si un producto no existe o se agotó, abortamos todo el pedido
                    http_response_code(400); 
                    echo json_encode(array(
                        "status" => "error",
                        "mensaje" => "El producto con ID " . $item->id_producto . " no existe o ya no está disponible."
                    ));
                    return; // Detenemos la ejecución del script aquí mismo
                }
            }

            // =========================================================
            // RECALCULAMOS LOS TOTALES DEL ENCABEZADO
            // =========================================================
            // Sobrescribimos lo que mandó la app con nuestra suma segura
            $data->subtotal = $subtotal_real_calculado;
            
            // Calculamos el Total (Subtotal + Envío). 
            // Nota: En un sistema 100% estricto, el costo_envio también se calcularía en el backend usando Google Maps API.
            $data->total = $subtotal_real_calculado + $data->costo_envio;


            // =========================================================
            // GUARDAMOS EN LA BASE DE DATOS (Transacción segura)
            // =========================================================
            // Le pasamos al modelo el $data recalculado y el arreglo de productos limpios
            $resultado_id = $this->pedidoModel->crearPedidoCompleto($data, $productos_validados);

            if ($resultado_id) {
                http_response_code(201); 
                echo json_encode(array(
                    "status" => "success",
                    "mensaje" => "Pedido creado exitosamente.",
                    "id_pedido" => $resultado_id,
                    "total_cobrado" => $data->total // Le avisamos a la app cuánto se le cobró realmente
                ));
            } else {
                http_response_code(503); 
                echo json_encode(array("status" => "error", "mensaje" => "Error de base de datos al procesar el pedido."));
            }

        } else {
            http_response_code(400); 
            echo json_encode(array("status" => "error", "mensaje" => "Faltan datos obligatorios o el carrito está vacío."));
        }
    }
}
?>