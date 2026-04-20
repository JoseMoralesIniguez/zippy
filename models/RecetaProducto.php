<?php

class RecetaProducto {
    private $conn;
    private $table_name = "recetas_producto";

    // Propiedades de la tabla
    public $id_receta;
    public $id_producto;
    public $id_insumo;
    public $cantidad_requerida; // DECIMAL(10,4)

    // Constructor con conexión a base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Agregar un ingrediente a la receta de un producto
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_producto, id_insumo, cantidad_requerida) 
                  VALUES (:id_producto, :id_insumo, :cantidad_requerida)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar
        $this->id_producto = htmlspecialchars(strip_tags($this->id_producto));
        $this->id_insumo = htmlspecialchars(strip_tags($this->id_insumo));
        $this->cantidad_requerida = htmlspecialchars(strip_tags($this->cantidad_requerida));

        // Vincular
        $stmt->bindParam(":id_producto", $this->id_producto);
        $stmt->bindParam(":id_insumo", $this->id_insumo);
        $stmt->bindParam(":cantidad_requerida", $this->cantidad_requerida);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 2. Obtener la receta completa de un Producto (con JOIN a Insumos)
     * Esto es vital para el momento de la venta, para saber qué descontar.
     */
    public function obtenerPorProducto($id_producto) {
        $query = "SELECT 
                    r.id_receta, 
                    r.id_producto, 
                    r.id_insumo, 
                    r.cantidad_requerida,
                    i.nombre as nombre_insumo,
                    i.unidad_medida,
                    i.costo_unitario
                  FROM " . $this->table_name . " r
                  INNER JOIN insumos i ON r.id_insumo = i.id_insumo
                  WHERE r.id_producto = :id_producto";

        $stmt = $this->conn->prepare($query);
        $id_producto = htmlspecialchars(strip_tags($id_producto));
        $stmt->bindParam(":id_producto", $id_producto);
        $stmt->execute();

        return $stmt;
    }

    /**
     * 3. Obtener el detalle de una sola línea de receta
     */
    public function obtenerPorId() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_receta = :id_receta LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $this->id_receta = htmlspecialchars(strip_tags($this->id_receta));
        $stmt->bindParam(":id_receta", $this->id_receta);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_producto = $row['id_producto'];
            $this->id_insumo = $row['id_insumo'];
            $this->cantidad_requerida = $row['cantidad_requerida'];
            return true;
        }
        return false;
    }

    /**
     * 4. Actualizar la cantidad de un ingrediente (Ej: si ahora la orden lleva más pollo)
     */
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET cantidad_requerida = :cantidad_requerida 
                  WHERE id_receta = :id_receta";

        $stmt = $this->conn->prepare($query);

        $this->cantidad_requerida = htmlspecialchars(strip_tags($this->cantidad_requerida));
        $this->id_receta = htmlspecialchars(strip_tags($this->id_receta));

        $stmt->bindParam(":cantidad_requerida", $this->cantidad_requerida);
        $stmt->bindParam(":id_receta", $this->id_receta);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 5. Eliminar un ingrediente de la receta
     */
    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_receta = :id_receta";

        $stmt = $this->conn->prepare($query);
        $this->id_receta = htmlspecialchars(strip_tags($this->id_receta));
        $stmt->bindParam(":id_receta", $this->id_receta);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>