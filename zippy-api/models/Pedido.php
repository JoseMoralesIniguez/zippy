<?php
class Pedido {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crearPedidoCompleto($datos, $productos) {
        try {
            // Iniciamos la transacción de seguridad
            $this->conn->beginTransaction();

            // 1. Insertar el encabezado en la tabla Pedidos
            $query_pedido = "INSERT INTO Pedidos 
                (id_cliente, id_restaurante, subtotal, costo_envio, total, metodo_pago, direccion_entrega_lat, direccion_entrega_lng) 
                VALUES (:cliente, :restaurante, :subtotal, :envio, :total, :pago, :lat, :lng)";
            
            $stmt_pedido = $this->conn->prepare($query_pedido);
            $stmt_pedido->bindParam(':cliente', $datos->id_cliente);
            $stmt_pedido->bindParam(':restaurante', $datos->id_restaurante);
            $stmt_pedido->bindParam(':subtotal', $datos->subtotal);
            $stmt_pedido->bindParam(':envio', $datos->costo_envio);
            $stmt_pedido->bindParam(':total', $datos->total);
            $stmt_pedido->bindParam(':pago', $datos->metodo_pago);
            $stmt_pedido->bindParam(':lat', $datos->direccion_entrega_lat);
            $stmt_pedido->bindParam(':lng', $datos->direccion_entrega_lng);
            
            $stmt_pedido->execute();

            // Obtenemos el ID del pedido que se acaba de crear
            $id_pedido_generado = $this->conn->lastInsertId();

            // 2. Insertar cada producto en Detalle_Pedido
            $query_detalle = "INSERT INTO Detalle_Pedido 
                (id_pedido, id_producto, cantidad, precio_unitario, instrucciones_especiales) 
                VALUES (:pedido, :producto, :cantidad, :precio, :instrucciones)";
            
            $stmt_detalle = $this->conn->prepare($query_detalle);

            foreach ($productos as $item) {
                $stmt_detalle->bindParam(':pedido', $id_pedido_generado);
                $stmt_detalle->bindParam(':producto', $item->id_producto);
                $stmt_detalle->bindParam(':cantidad', $item->cantidad);
                $stmt_detalle->bindParam(':precio', $item->precio_unitario);
                
                // Manejar si viene vacío o nulo
                $instrucciones = isset($item->instrucciones_especiales) ? $item->instrucciones_especiales : null;
                $stmt_detalle->bindParam(':instrucciones', $instrucciones);
                
                $stmt_detalle->execute();
            }

            // Si todo salió bien, confirmamos los cambios en la base de datos
            $this->conn->commit();
            return $id_pedido_generado;

        } catch (PDOException $e) {
            // Si algo falla, revertimos absolutamente todo
            $this->conn->rollBack();
            // Registramos el error internamente (opcional)
            error_log("Error al crear pedido: " . $e->getMessage());
            return false;
        }
    }
}
?>