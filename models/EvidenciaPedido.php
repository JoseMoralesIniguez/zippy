<?php

class EvidenciaPedido {
    private $conn;
    private $table_name = "evidencias_pedido";

    // Propiedades de la tabla
    public $id_evidencia;
    public $id_pedido;
    public $subido_por; // ENUM: ej. 'Repartidor', 'Restaurante', 'Cliente'
    public $url_foto;
    public $comentario_evidencia;
    public $fecha_subida;

    // Constructor con conexión a base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Crear (Registrar) una nueva evidencia fotográfica
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_pedido, subido_por, url_foto, comentario_evidencia) 
                  VALUES (:id_pedido, :subido_por, :url_foto, :comentario_evidencia)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos obligatorios
        $this->id_pedido = htmlspecialchars(strip_tags($this->id_pedido));
        $this->subido_por = htmlspecialchars(strip_tags($this->subido_por));
        $this->url_foto = htmlspecialchars(strip_tags($this->url_foto));
        
        // Sanitizar el comentario (que puede venir nulo)
        if ($this->comentario_evidencia !== null) {
            $this->comentario_evidencia = htmlspecialchars(strip_tags($this->comentario_evidencia));
        }

        // Vincular parámetros
        $stmt->bindParam(":id_pedido", $this->id_pedido);
        $stmt->bindParam(":subido_por", $this->subido_por);
        $stmt->bindParam(":url_foto", $this->url_foto);
        $stmt->bindParam(":comentario_evidencia", $this->comentario_evidencia);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 2. Obtener todas las evidencias de un pedido específico
     */
    public function obtenerPorPedido($id_pedido) {
        $query = "SELECT id_evidencia, id_pedido, subido_por, url_foto, comentario_evidencia, fecha_subida 
                  FROM " . $this->table_name . " 
                  WHERE id_pedido = :id_pedido 
                  ORDER BY fecha_subida ASC";

        $stmt = $this->conn->prepare($query);
        
        $id_pedido = htmlspecialchars(strip_tags($id_pedido));
        $stmt->bindParam(":id_pedido", $id_pedido);
        
        $stmt->execute();
        return $stmt;
    }

    /**
     * 3. Obtener el detalle de UNA sola evidencia por su ID
     */
    public function obtenerPorId() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_evidencia = :id_evidencia 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        
        $this->id_evidencia = htmlspecialchars(strip_tags($this->id_evidencia));
        $stmt->bindParam(":id_evidencia", $this->id_evidencia);
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_pedido = $row['id_pedido'];
            $this->subido_por = $row['subido_por'];
            $this->url_foto = $row['url_foto'];
            $this->comentario_evidencia = $row['comentario_evidencia'];
            $this->fecha_subida = $row['fecha_subida'];
            return true;
        }
        return false;
    }

    /**
     * 4. Eliminar una evidencia
     * (Importante: Esto solo borra el registro en la BD. La eliminación física 
     * del archivo de imagen del servidor se debe manejar en el Controlador).
     */
    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id_evidencia = :id_evidencia";

        $stmt = $this->conn->prepare($query);

        $this->id_evidencia = htmlspecialchars(strip_tags($this->id_evidencia));
        $stmt->bindParam(":id_evidencia", $this->id_evidencia);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>