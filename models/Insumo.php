<?php

class Insumo {
    private $conn;
    private $table_name = "insumos";

    // Propiedades de la tabla
    public $id_insumo;
    public $id_restaurante;
    public $nombre;
    public $unidad_medida; // ENUM: 'Kg', 'Gramos', 'Litros', 'Mililitros', 'Piezas'
    public $costo_unitario;
    public $stock_actual;
    public $stock_minimo;
    public $fecha_ultima_actualizacion;

    // Constructor con conexión a base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Registrar un nuevo insumo en el almacén
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_restaurante, nombre, unidad_medida, costo_unitario, stock_actual, stock_minimo) 
                  VALUES 
                  (:id_restaurante, :nombre, :unidad_medida, :costo_unitario, :stock_actual, :stock_minimo)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->id_restaurante = htmlspecialchars(strip_tags($this->id_restaurante));
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->unidad_medida = htmlspecialchars(strip_tags($this->unidad_medida));
        
        // Manejar valores por defecto si no vienen
        $this->costo_unitario = isset($this->costo_unitario) ? $this->costo_unitario : 0.00;
        $this->stock_actual = isset($this->stock_actual) ? $this->stock_actual : 0.0000;
        $this->stock_minimo = isset($this->stock_minimo) ? $this->stock_minimo : 0.0000;

        // Vincular parámetros
        $stmt->bindParam(":id_restaurante", $this->id_restaurante);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":unidad_medida", $this->unidad_medida);
        $stmt->bindParam(":costo_unitario", $this->costo_unitario);
        $stmt->bindParam(":stock_actual", $this->stock_actual);
        $stmt->bindParam(":stock_minimo", $this->stock_minimo);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 2. Obtener todo el inventario de un Restaurante
     */
    public function obtenerPorRestaurante($id_restaurante) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_restaurante = :id_restaurante 
                  ORDER BY nombre ASC";

        $stmt = $this->conn->prepare($query);
        $id_restaurante = htmlspecialchars(strip_tags($id_restaurante));
        $stmt->bindParam(":id_restaurante", $id_restaurante);
        $stmt->execute();

        return $stmt;
    }

    /**
     * 3. Obtener el detalle de un insumo específico
     */
    public function obtenerPorId() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_insumo = :id_insumo LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $this->id_insumo = htmlspecialchars(strip_tags($this->id_insumo));
        $stmt->bindParam(":id_insumo", $this->id_insumo);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_restaurante = $row['id_restaurante'];
            $this->nombre = $row['nombre'];
            $this->unidad_medida = $row['unidad_medida'];
            $this->costo_unitario = $row['costo_unitario'];
            $this->stock_actual = $row['stock_actual'];
            $this->stock_minimo = $row['stock_minimo'];
            $this->fecha_ultima_actualizacion = $row['fecha_ultima_actualizacion'];
            return true;
        }
        return false;
    }

    /**
     * 4. Actualizar información general del insumo (NO ACTUALIZA EL STOCK)
     * Se usa para corregir el nombre, cambiar la unidad o el costo unitario.
     */
    public function actualizarInfo() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre = :nombre, 
                      unidad_medida = :unidad_medida, 
                      costo_unitario = :costo_unitario, 
                      stock_minimo = :stock_minimo 
                  WHERE id_insumo = :id_insumo";

        $stmt = $this->conn->prepare($query);

        // Sanitizar
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->unidad_medida = htmlspecialchars(strip_tags($this->unidad_medida));
        $this->costo_unitario = htmlspecialchars(strip_tags($this->costo_unitario));
        $this->stock_minimo = htmlspecialchars(strip_tags($this->stock_minimo));
        $this->id_insumo = htmlspecialchars(strip_tags($this->id_insumo));

        // Vincular
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":unidad_medida", $this->unidad_medida);
        $stmt->bindParam(":costo_unitario", $this->costo_unitario);
        $stmt->bindParam(":stock_minimo", $this->stock_minimo);
        $stmt->bindParam(":id_insumo", $this->id_insumo);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 5. Actualizar el Stock Matemáticamente (Suma o Resta)
     * Este método será llamado por el controlador de "Movimientos" cada vez que vendas o compres.
     * $cantidad_cambio puede ser positivo (ej: 5.0) o negativo (ej: -0.25)
     */
    public function actualizarStock($cantidad_cambio) {
        // En SQL hacemos: stock_actual = stock_actual + cantidad_cambio
        $query = "UPDATE " . $this->table_name . " 
                  SET stock_actual = stock_actual + :cantidad_cambio 
                  WHERE id_insumo = :id_insumo";

        $stmt = $this->conn->prepare($query);

        $cantidad_cambio = htmlspecialchars(strip_tags($cantidad_cambio));
        $this->id_insumo = htmlspecialchars(strip_tags($this->id_insumo));

        $stmt->bindParam(":cantidad_cambio", $cantidad_cambio);
        $stmt->bindParam(":id_insumo", $this->id_insumo);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 6. Alerta de Compras: Obtener insumos que están por debajo del stock mínimo
     */
    public function alertarStockBajo($id_restaurante) {
        $query = "SELECT id_insumo, nombre, stock_actual, stock_minimo, unidad_medida 
                  FROM " . $this->table_name . " 
                  WHERE id_restaurante = :id_restaurante 
                  AND stock_actual <= stock_minimo";

        $stmt = $this->conn->prepare($query);
        $id_restaurante = htmlspecialchars(strip_tags($id_restaurante));
        $stmt->bindParam(":id_restaurante", $id_restaurante);
        $stmt->execute();

        return $stmt;
    }
}
?>