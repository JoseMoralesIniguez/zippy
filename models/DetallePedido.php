<?php

class DetallePedido {
    private $conn;
    private $table_name = "detalle_pedido";

    // Propiedades de la tabla
    public $id_detalle;
    public $id_pedido;
    public $id_producto;
    public $cantidad;
    public $precio_unitario;
    public $instrucciones_especiales;

    // Constructor con conexión a base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Crear un nuevo detalle para un pedido
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_pedido, id_producto, cantidad, precio_unitario, instrucciones_especiales) 
                  VALUES (:id_pedido, :id_producto, :cantidad, :precio_unitario, :instrucciones_especiales)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->id_pedido = htmlspecialchars(strip_tags($this->id_pedido));
        $this->id_producto = htmlspecialchars(strip_tags($this->id_producto));
        $this->cantidad = htmlspecialchars(strip_tags($this->cantidad));
        $this->precio_unitario = htmlspecialchars(strip_tags($this->precio_unitario));
        // Permitimos nulos en instrucciones, pero sanitizamos si trae algo
        $this->instrucciones_especiales = htmlspecialchars(strip_tags($this->instrucciones_especiales));

        // Vincular parámetros
        $stmt->bindParam(":id_pedido", $this->id_pedido);
        $stmt->bindParam(":id_producto", $this->id_producto);
        $stmt->bindParam(":cantidad", $this->cantidad);
        $stmt->bindParam(":precio_unitario", $this->precio_unitario);
        $stmt->bindParam(":instrucciones_especiales", $this->instrucciones_especiales);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 2. Obtener todos los detalles de un pedido específico
     * (Ideal para mostrar el resumen del carrito o el ticket final)
     */
    public function obtenerPorPedido($id_pedido) {
        // Nota: En una aplicación real, a menudo querrás hacer un JOIN con 
        // la tabla 'productos' aquí para traer también el nombre del producto
        $query = "SELECT id_detalle, id_pedido, id_producto, cantidad, precio_unitario, instrucciones_especiales 
                  FROM " . $this->table_name . " 
                  WHERE id_pedido = :id_pedido";

        $stmt = $this->conn->prepare($query);
        
        $id_pedido = htmlspecialchars(strip_tags($id_pedido));
        $stmt->bindParam(":id_pedido", $id_pedido);
        
        $stmt->execute();
        return $stmt;
    }

    /**
     * 3. Obtener un detalle individual por su ID
     */
    public function obtenerPorId() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_detalle = :id_detalle 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        
        $this->id_detalle = htmlspecialchars(strip_tags($this->id_detalle));
        $stmt->bindParam(":id_detalle", $this->id_detalle);
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_pedido = $row['id_pedido'];
            $this->id_producto = $row['id_producto'];
            $this->cantidad = $row['cantidad'];
            $this->precio_unitario = $row['precio_unitario'];
            $this->instrucciones_especiales = $row['instrucciones_especiales'];
            return true;
        }
        return false;
    }

    /**
     * 4. Eliminar un detalle (Útil si el pedido aún está en estado "Carrito")
     */
    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id_detalle = :id_detalle";

        $stmt = $this->conn->prepare($query);

        $this->id_detalle = htmlspecialchars(strip_tags($this->id_detalle));
        $stmt->bindParam(":id_detalle", $this->id_detalle);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>