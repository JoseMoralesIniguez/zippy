<?php

class LiquidacionRestaurante {
    private $conn;
    private $table_name = "liquidaciones_restaurantes";

    // Propiedades de la tabla
    public $id_liquidacion;
    public $id_restaurante;
    public $monto_total;
    public $fecha_inicio;
    public $fecha_fin;
    public $estatus; // ENUM: 'Pendiente', 'Pagado', 'Cancelado'
    public $metodo_pago;
    public $referencia_bancaria;
    public $fecha_creacion;
    public $fecha_pago;

    // Constructor con conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Crear un nuevo corte semanal para el restaurante
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_restaurante, monto_total, fecha_inicio, fecha_fin, estatus) 
                  VALUES (:id_restaurante, :monto_total, :fecha_inicio, :fecha_fin, :estatus)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->id_restaurante = htmlspecialchars(strip_tags($this->id_restaurante));
        $this->monto_total = htmlspecialchars(strip_tags($this->monto_total));
        $this->fecha_inicio = htmlspecialchars(strip_tags($this->fecha_inicio));
        $this->fecha_fin = htmlspecialchars(strip_tags($this->fecha_fin));
        $this->estatus = htmlspecialchars(strip_tags($this->estatus)); 

        // Vincular parámetros
        $stmt->bindParam(":id_restaurante", $this->id_restaurante);
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
     * 2. Obtener el historial de pagos de un restaurante específico
     */
    public function obtenerPorRestaurante($id_restaurante) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_restaurante = :id_restaurante 
                  ORDER BY fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        
        $id_restaurante = htmlspecialchars(strip_tags($id_restaurante));
        $stmt->bindParam(":id_restaurante", $id_restaurante);
        
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
            $this->id_restaurante = $row['id_restaurante'];
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
     * 4. Registrar el pago de ganancias al restaurante
     */
    public function registrarPago() {
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