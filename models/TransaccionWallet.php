<?php

class TransaccionWallet {
    private $conn;
    private $table_name = "transacciones_wallet";

    // Propiedades de la tabla
    public $id_transaccion;
    public $id_usuario;
    public $tipo_movimiento; // ENUM: ej. 'Ingreso' (suma saldo), 'Egreso' (resta saldo)
    public $monto;
    public $descripcion; // Nullable
    public $id_pedido_relacionado; // Nullable
    public $id_reclamo_relacionado; // Nullable
    public $fecha_transaccion;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Registrar una nueva transacción en la Wallet
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_usuario, tipo_movimiento, monto, descripcion, id_pedido_relacionado, id_reclamo_relacionado) 
                  VALUES 
                  (:id_usuario, :tipo_movimiento, :monto, :descripcion, :id_pedido_relacionado, :id_reclamo_relacionado)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos obligatorios
        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));
        $this->tipo_movimiento = htmlspecialchars(strip_tags($this->tipo_movimiento));
        $this->monto = htmlspecialchars(strip_tags($this->monto));
        
        // Sanitizar datos opcionales
        $this->descripcion = $this->descripcion !== null ? htmlspecialchars(strip_tags($this->descripcion)) : null;
        $this->id_pedido_relacionado = $this->id_pedido_relacionado !== null ? htmlspecialchars(strip_tags($this->id_pedido_relacionado)) : null;
        $this->id_reclamo_relacionado = $this->id_reclamo_relacionado !== null ? htmlspecialchars(strip_tags($this->id_reclamo_relacionado)) : null;

        // Vincular parámetros
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->bindParam(":tipo_movimiento", $this->tipo_movimiento);
        $stmt->bindParam(":monto", $this->monto);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":id_pedido_relacionado", $this->id_pedido_relacionado);
        $stmt->bindParam(":id_reclamo_relacionado", $this->id_reclamo_relacionado);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 2. Obtener el historial de movimientos de un usuario
     */
    public function obtenerHistorialUsuario($id_usuario) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_usuario = :id_usuario 
                  ORDER BY fecha_transaccion DESC"; // Del más reciente al más antiguo

        $stmt = $this->conn->prepare($query);
        $id_usuario = htmlspecialchars(strip_tags($id_usuario));
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->execute();

        return $stmt;
    }

    /**
     * 3. Calcular el saldo actual del usuario en su Wallet
     * Suma los 'Ingresos' y resta los 'Egresos' para dar el saldo total.
     */
    public function calcularSaldo($id_usuario) {
        // Usamos CASE en SQL para sumar o restar dependiendo del tipo de movimiento
        $query = "SELECT 
                    SUM(
                        CASE 
                            WHEN tipo_movimiento = 'Ingreso' THEN monto 
                            WHEN tipo_movimiento = 'Egreso' THEN -monto 
                            ELSE 0 
                        END
                    ) as saldo_actual 
                  FROM " . $this->table_name . " 
                  WHERE id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($query);
        $id_usuario = htmlspecialchars(strip_tags($id_usuario));
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si no hay movimientos, el saldo es 0
        return $row['saldo_actual'] ? $row['saldo_actual'] : 0.00;
    }

    /**
     * 4. Obtener detalle de una transacción específica
     */
    public function obtenerPorId() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_transaccion = :id_transaccion LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $this->id_transaccion = htmlspecialchars(strip_tags($this->id_transaccion));
        $stmt->bindParam(":id_transaccion", $this->id_transaccion);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_usuario = $row['id_usuario'];
            $this->tipo_movimiento = $row['tipo_movimiento'];
            $this->monto = $row['monto'];
            $this->descripcion = $row['descripcion'];
            $this->id_pedido_relacionado = $row['id_pedido_relacionado'];
            $this->id_reclamo_relacionado = $row['id_reclamo_relacionado'];
            $this->fecha_transaccion = $row['fecha_transaccion'];
            return true;
        }
        return false;
    }
}
?>