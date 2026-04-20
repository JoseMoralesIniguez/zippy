<?php

class ReclamoProducto {
    private $conn;
    private $table_name = "reclamos_producto";

    // Propiedades de la tabla
    public $id_reclamo;
    public $id_detalle;
    public $motivo; // ENUM: ej. 'Faltante', 'Mal estado', 'Equivocado'
    public $comentario_cliente;
    public $monto_solicitado;
    public $url_evidencia_cliente; // Nullable (Foto del problema)
    public $estado_resolucion; // ENUM: 'Pendiente', 'Aprobado', 'Rechazado'
    public $monto_reembolsado; // Nullable
    public $metodo_reembolso; // ENUM (Nullable): ej. 'Saldo Zippy', 'Tarjeta', 'Efectivo'
    public $fecha_reclamo;
    public $fecha_resolucion; // Nullable

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Crear un nuevo reclamo
     * Generado por el cliente cuando algo sale mal con un producto específico.
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_detalle, motivo, comentario_cliente, monto_solicitado, url_evidencia_cliente, estado_resolucion) 
                  VALUES 
                  (:id_detalle, :motivo, :comentario_cliente, :monto_solicitado, :url_evidencia_cliente, 'Pendiente')";

        $stmt = $this->conn->prepare($query);

        // Sanitizar
        $this->id_detalle = htmlspecialchars(strip_tags($this->id_detalle));
        $this->motivo = htmlspecialchars(strip_tags($this->motivo));
        $this->comentario_cliente = htmlspecialchars(strip_tags($this->comentario_cliente));
        $this->monto_solicitado = htmlspecialchars(strip_tags($this->monto_solicitado));
        
        if ($this->url_evidencia_cliente !== null) {
            $this->url_evidencia_cliente = htmlspecialchars(strip_tags($this->url_evidencia_cliente));
        }

        // Vincular
        $stmt->bindParam(":id_detalle", $this->id_detalle);
        $stmt->bindParam(":motivo", $this->motivo);
        $stmt->bindParam(":comentario_cliente", $this->comentario_cliente);
        $stmt->bindParam(":monto_solicitado", $this->monto_solicitado);
        $stmt->bindParam(":url_evidencia_cliente", $this->url_evidencia_cliente);
        // El estado 'Pendiente' ya se envió directo en la consulta SQL

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 2. Obtener un reclamo por su ID para revisarlo
     */
    public function obtenerPorId() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_reclamo = :id_reclamo LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $this->id_reclamo = htmlspecialchars(strip_tags($this->id_reclamo));
        $stmt->bindParam(":id_reclamo", $this->id_reclamo);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_detalle = $row['id_detalle'];
            $this->motivo = $row['motivo'];
            $this->comentario_cliente = $row['comentario_cliente'];
            $this->monto_solicitado = $row['monto_solicitado'];
            $this->url_evidencia_cliente = $row['url_evidencia_cliente'];
            $this->estado_resolucion = $row['estado_resolucion'];
            $this->monto_reembolsado = $row['monto_reembolsado'];
            $this->metodo_reembolso = $row['metodo_reembolso'];
            $this->fecha_reclamo = $row['fecha_reclamo'];
            $this->fecha_resolucion = $row['fecha_resolucion'];
            return true;
        }
        return false;
    }

    /**
     * 3. Resolver el reclamo (Aprobar o Rechazar)
     * Utilizado por el administrador de Zippy o soporte técnico.
     */
    public function resolverReclamo() {
        // Al resolver, actualizamos el estado, el dinero reembolsado y estampamos la fecha actual
        $query = "UPDATE " . $this->table_name . " 
                  SET estado_resolucion = :estado_resolucion, 
                      monto_reembolsado = :monto_reembolsado, 
                      metodo_reembolso = :metodo_reembolso, 
                      fecha_resolucion = CURRENT_TIMESTAMP 
                  WHERE id_reclamo = :id_reclamo";

        $stmt = $this->conn->prepare($query);

        // Sanitizar
        $this->estado_resolucion = htmlspecialchars(strip_tags($this->estado_resolucion));
        $this->monto_reembolsado = htmlspecialchars(strip_tags($this->monto_reembolsado));
        $this->id_reclamo = htmlspecialchars(strip_tags($this->id_reclamo));
        
        if ($this->metodo_reembolso !== null) {
            $this->metodo_reembolso = htmlspecialchars(strip_tags($this->metodo_reembolso));
        }

        // Vincular
        $stmt->bindParam(":estado_resolucion", $this->estado_resolucion);
        $stmt->bindParam(":monto_reembolsado", $this->monto_reembolsado);
        $stmt->bindParam(":metodo_reembolso", $this->metodo_reembolso);
        $stmt->bindParam(":id_reclamo", $this->id_reclamo);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 4. Listar todos los reclamos Pendientes (Bandeja de entrada de Soporte)
     */
    public function obtenerReclamosPendientes() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE estado_resolucion = 'Pendiente' 
                  ORDER BY fecha_reclamo ASC"; // Los más antiguos primero

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>