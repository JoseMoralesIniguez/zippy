<?php

class Resena {
    private $conn;
    private $table_name = "resenas";

    // Propiedades de la tabla
    public $id_resena;
    public $id_pedido; // Único (UNI)
    public $id_cliente;
    public $calificacion_restaurante; // TINYINT (ej. 1 a 5)
    public $comentario_restaurante;
    public $calificacion_repartidor; // TINYINT (ej. 1 a 5)
    public $comentario_repartidor;
    public $fecha_resena;

    // Constructor con conexión a base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Crear una nueva reseña
     * El cliente califica su experiencia al finalizar el pedido.
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_pedido, id_cliente, calificacion_restaurante, comentario_restaurante, 
                   calificacion_repartidor, comentario_repartidor) 
                  VALUES 
                  (:id_pedido, :id_cliente, :calificacion_restaurante, :comentario_restaurante, 
                   :calificacion_repartidor, :comentario_repartidor)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos obligatorios
        $this->id_pedido = htmlspecialchars(strip_tags($this->id_pedido));
        $this->id_cliente = htmlspecialchars(strip_tags($this->id_cliente));

        // Sanitizar datos opcionales (por si el cliente solo califica a uno de los dos)
        $this->calificacion_restaurante = $this->calificacion_restaurante !== null ? htmlspecialchars(strip_tags($this->calificacion_restaurante)) : null;
        $this->comentario_restaurante = $this->comentario_restaurante !== null ? htmlspecialchars(strip_tags($this->comentario_restaurante)) : null;
        
        $this->calificacion_repartidor = $this->calificacion_repartidor !== null ? htmlspecialchars(strip_tags($this->calificacion_repartidor)) : null;
        $this->comentario_repartidor = $this->comentario_repartidor !== null ? htmlspecialchars(strip_tags($this->comentario_repartidor)) : null;

        // Vincular parámetros
        $stmt->bindParam(":id_pedido", $this->id_pedido);
        $stmt->bindParam(":id_cliente", $this->id_cliente);
        $stmt->bindParam(":calificacion_restaurante", $this->calificacion_restaurante);
        $stmt->bindParam(":comentario_restaurante", $this->comentario_restaurante);
        $stmt->bindParam(":calificacion_repartidor", $this->calificacion_repartidor);
        $stmt->bindParam(":comentario_repartidor", $this->comentario_repartidor);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 2. Obtener la reseña de un pedido específico
     */
    public function obtenerPorPedido() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_pedido = :id_pedido 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        
        $this->id_pedido = htmlspecialchars(strip_tags($this->id_pedido));
        $stmt->bindParam(":id_pedido", $this->id_pedido);
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_resena = $row['id_resena'];
            $this->id_cliente = $row['id_cliente'];
            $this->calificacion_restaurante = $row['calificacion_restaurante'];
            $this->comentario_restaurante = $row['comentario_restaurante'];
            $this->calificacion_repartidor = $row['calificacion_repartidor'];
            $this->comentario_repartidor = $row['comentario_repartidor'];
            $this->fecha_resena = $row['fecha_resena'];
            return true;
        }
        return false;
    }

    /**
     * 3. Obtener el promedio y total de reseñas de un Restaurante (Requiere JOIN con tabla pedidos)
     * ¡Ideal para mostrar las "estrellitas" en la app del cliente!
     */
    public function obtenerEstadisticasRestaurante($id_restaurante) {
        $query = "SELECT 
                    COUNT(r.id_resena) as total_resenas, 
                    AVG(r.calificacion_restaurante) as promedio_estrellas
                  FROM " . $this->table_name . " r
                  INNER JOIN pedidos p ON r.id_pedido = p.id_pedido
                  WHERE p.id_restaurante = :id_restaurante AND r.calificacion_restaurante IS NOT NULL";

        $stmt = $this->conn->prepare($query);
        $id_restaurante = htmlspecialchars(strip_tags($id_restaurante));
        $stmt->bindParam(":id_restaurante", $id_restaurante);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 4. Eliminar una reseña (Por si un comentario viola políticas de la app)
     */
    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id_resena = :id_resena";

        $stmt = $this->conn->prepare($query);
        $this->id_resena = htmlspecialchars(strip_tags($this->id_resena));
        $stmt->bindParam(":id_resena", $this->id_resena);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>