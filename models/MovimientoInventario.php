<?php

class MovimientoInventario {
    private $conn;
    private $table_name = "movimientos_inventario";

    public $id_movimiento;
    public $id_insumo;
    public $tipo_movimiento; // ENUM: 'Compra', 'Venta', 'Merma', 'Ajuste Manual'
    public $cantidad; // Negativo para salidas, positivo para entradas
    public $id_pedido_relacionado; // Nullable
    public $nota;
    public $fecha_movimiento;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * REGISTRAR MOVIMIENTO Y ACTUALIZAR STOCK
     */
    public function registrar() {
        try {
            // Iniciamos una transacción de base de datos para que si algo falla, no se altere el stock
            $this->conn->beginTransaction();

            $query = "INSERT INTO " . $this->table_name . " 
                      (id_insumo, tipo_movimiento, cantidad, id_pedido_relacionado, nota) 
                      VALUES (:id_insumo, :tipo_movimiento, :cantidad, :id_pedido_relacionado, :nota)";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":id_insumo", $this->id_insumo);
            $stmt->bindParam(":tipo_movimiento", $this->tipo_movimiento);
            $stmt->bindParam(":cantidad", $this->cantidad);
            $stmt->bindParam(":id_pedido_relacionado", $this->id_pedido_relacionado);
            $stmt->bindParam(":nota", $this->nota);

            if($stmt->execute()) {
                // AQUÍ ESTÁ LA MAGIA: Actualizamos el stock en la tabla insumos
                include_once 'Insumo.php';
                $insumo = new Insumo($this->db);
                $insumo->id_insumo = $this->id_insumo;
                
                // Si el método actualizarStock de tu modelo Insumo ya está listo:
                if($insumo->actualizarStock($this->cantidad)) {
                    $this->conn->commit(); // Todo salió bien, guardamos cambios
                    return true;
                }
            }

            $this->conn->rollBack(); // Algo falló, revertimos
            return false;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function obtenerHistorialPorInsumo($id_insumo) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_insumo = :id_insumo ORDER BY fecha_movimiento DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_insumo", $id_insumo);
        $stmt->execute();
        return $stmt;
    }
}
?>