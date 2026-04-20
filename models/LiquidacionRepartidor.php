<?php

class LiquidacionRepartidor {
    private $conn;
    private $table_name = "liquidaciones_repartidores";

    // Propiedades de la tabla
    public $id_liquidacion;
    public $id_repartidor;
    public $monto_total;
    public $fecha_inicio;
    public $fecha_fin;
    public $estatus; // ENUM: ej. 'Pendiente', 'Pagado', 'Cancelado'
    public $metodo_pago;
    public $referencia_bancaria;
    public $fecha_creacion;
    public $fecha_pago;

    // Constructor con conexión a base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Crear una nueva liquidación (Corte semanal)
     * Se genera cuando el sistema calcula cuánto se le debe al repartidor en un periodo.
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_repartidor, monto_total, fecha_inicio, fecha_fin, estatus) 
                  VALUES (:id_repartidor, :monto_total, :fecha_inicio, :fecha_fin, :estatus)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->id_repartidor = htmlspecialchars(strip_tags($this->id_repartidor));
        $this->monto_total = htmlspecialchars(strip_tags($this->monto_total));
        $this->fecha_inicio = htmlspecialchars(strip_tags($this->fecha_inicio));
        $this->fecha_fin = htmlspecialchars(strip_tags($this->fecha_fin));
        $this->estatus = htmlspecialchars(strip_tags($this->estatus)); 
        // Por defecto debería ser 'Pendiente' desde el controlador

        // Vincular parámetros
        $stmt->bindParam(":id_repartidor", $this->id_repartidor);
        $stmt->bindParam(":monto_total", $this->monto_total);
        $stmt->bindParam(":fecha_inicio", $this->fecha_inicio);
        $stmt->bindParam(":fecha_fin", $this->fecha_fin);
        $stmt->bindParam(":estatus", $this->estatus);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 2. Obtener el historial de liquidaciones de un repartidor específico
     */
    public function obtenerPorRepartidor($id_repartidor) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_repartidor = :id_repartidor 
                  ORDER BY fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        
        $id_repartidor = htmlspecialchars(strip_tags($id_repartidor));
        $stmt->bindParam(":id_repartidor", $id_repartidor);
        
        $stmt->execute();
        return $stmt;
    }

    /**
     * 3. Obtener el detalle de una liquidación específica
     */
    public function obtenerPorId() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_liquidacion = :id_liquidacion 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        
        $this->id_liquidacion = htmlspecialchars(strip_tags($this->id_liquidacion));
        $stmt->bindParam(":id_liquidacion", $this->id_liquidacion);
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_repartidor = $row['id_repartidor'];
            $this->monto_total = $row['monto_total'];
            $this->fecha_inicio = $row['fecha_inicio'];
            $this->fecha_fin = $row['fecha_fin'];
            $this->estatus = $row['estatus'];
            $this->metodo_pago = $row['metodo_pago'];
            $this->referencia_bancaria = $row['referencia_bancaria'];
            $this->fecha_creacion = $row['fecha_creacion'];
            $this->fecha_pago = $row['fecha_pago'];
            return true;
        }
        return false;
    }

    /**
     * 4. Registrar el pago (Actualizar a "Pagado")
     * Se usa cuando el administrador de Zyppy realiza la transferencia al repartidor.
     */
    public function registrarPago() {
        // Actualizamos estatus, método, referencia y estampamos la fecha y hora de pago
        $query = "UPDATE " . $this->table_name . " 
                  SET estatus = :estatus, 
                      metodo_pago = :metodo_pago, 
                      referencia_bancaria = :referencia_bancaria, 
                      fecha_pago = CURRENT_TIMESTAMP 
                  WHERE id_liquidacion = :id_liquidacion";

        $stmt = $this->conn->prepare($query);

        // Sanitizar
        $this->estatus = htmlspecialchars(strip_tags($this->estatus));
        $this->metodo_pago = htmlspecialchars(strip_tags($this->metodo_pago));
        $this->referencia_bancaria = htmlspecialchars(strip_tags($this->referencia_bancaria));
        $this->id_liquidacion = htmlspecialchars(strip_tags($this->id_liquidacion));

        // Vincular
        $stmt->bindParam(":estatus", $this->estatus);
        $stmt->bindParam(":metodo_pago", $this->metodo_pago);
        $stmt->bindParam(":referencia_bancaria", $this->referencia_bancaria);
        $stmt->bindParam(":id_liquidacion", $this->id_liquidacion);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>