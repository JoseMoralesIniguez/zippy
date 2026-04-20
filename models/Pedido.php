<?php

class Pedido {
    private $conn;
    private $table_name = "pedidos";

    // Propiedades de la tabla
    public $id_pedido;
    public $id_cliente;
    public $id_restaurante;
    public $id_repartidor; // Nullable
    public $estado_pedido; // ENUM: 'Pendiente', 'Preparando', 'En Camino', 'Entregado', 'Cancelado'
    public $subtotal;
    public $costo_envio;
    public $total;
    public $metodo_pago; // ENUM: 'Efectivo', 'Tarjeta', 'Transferencia'
    public $direccion_entrega_lat;
    public $direccion_entrega_lng;
    public $fecha_creacion;
    // NUEVO CAMPO:
    public $origen_pedido; // ENUM: 'App', 'Mostrador', 'Telefono', 'WhatsApp'

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Crear un nuevo pedido
     * Nace sin repartidor y en estado 'Pendiente' por defecto.
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_cliente, id_restaurante, estado_pedido, subtotal, costo_envio, total, 
                   metodo_pago, direccion_entrega_lat, direccion_entrega_lng, origen_pedido) 
                  VALUES 
                  (:id_cliente, :id_restaurante, :estado_pedido, :subtotal, :costo_envio, :total, 
                   :metodo_pago, :direccion_entrega_lat, :direccion_entrega_lng, :origen_pedido)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar
        $this->id_cliente = htmlspecialchars(strip_tags($this->id_cliente));
        $this->id_restaurante = htmlspecialchars(strip_tags($this->id_restaurante));
        $this->estado_pedido = htmlspecialchars(strip_tags($this->estado_pedido));
        $this->subtotal = htmlspecialchars(strip_tags($this->subtotal));
        $this->costo_envio = htmlspecialchars(strip_tags($this->costo_envio));
        $this->total = htmlspecialchars(strip_tags($this->total));
        $this->metodo_pago = htmlspecialchars(strip_tags($this->metodo_pago));
        
        // Manejar datos opcionales (Mostrador no lleva lat/lng, por ejemplo)
        $this->direccion_entrega_lat = $this->direccion_entrega_lat !== null ? htmlspecialchars(strip_tags($this->direccion_entrega_lat)) : null;
        $this->direccion_entrega_lng = $this->direccion_entrega_lng !== null ? htmlspecialchars(strip_tags($this->direccion_entrega_lng)) : null;
        
        // Si no nos mandan origen, asumimos que viene de la App
        $this->origen_pedido = isset($this->origen_pedido) ? htmlspecialchars(strip_tags($this->origen_pedido)) : 'App';

        // Vincular parámetros
        $stmt->bindParam(":id_cliente", $this->id_cliente);
        $stmt->bindParam(":id_restaurante", $this->id_restaurante);
        $stmt->bindParam(":estado_pedido", $this->estado_pedido);
        $stmt->bindParam(":subtotal", $this->subtotal);
        $stmt->bindParam(":costo_envio", $this->costo_envio);
        $stmt->bindParam(":total", $this->total);
        $stmt->bindParam(":metodo_pago", $this->metodo_pago);
        $stmt->bindParam(":direccion_entrega_lat", $this->direccion_entrega_lat);
        $stmt->bindParam(":direccion_entrega_lng", $this->direccion_entrega_lng);
        $stmt->bindParam(":origen_pedido", $this->origen_pedido);

        if($stmt->execute()) {
            $this->id_pedido = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    /**
     * 2. Obtener un pedido por su ID
     */
    public function obtenerPorId() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_pedido = :id_pedido LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $this->id_pedido = htmlspecialchars(strip_tags($this->id_pedido));
        $stmt->bindParam(":id_pedido", $this->id_pedido);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_cliente = $row['id_cliente'];
            $this->id_restaurante = $row['id_restaurante'];
            $this->id_repartidor = $row['id_repartidor'];
            $this->estado_pedido = $row['estado_pedido'];
            $this->subtotal = $row['subtotal'];
            $this->costo_envio = $row['costo_envio'];
            $this->total = $row['total'];
            $this->metodo_pago = $row['metodo_pago'];
            $this->direccion_entrega_lat = $row['direccion_entrega_lat'];
            $this->direccion_entrega_lng = $row['direccion_entrega_lng'];
            $this->fecha_creacion = $row['fecha_creacion'];
            // NUEVO CAMPO:
            $this->origen_pedido = $row['origen_pedido'];
            return true;
        }
        return false;
    }

    /**
     * 3. Asignar un Repartidor al pedido
     */
    public function asignarRepartidor() {
        $query = "UPDATE " . $this->table_name . " 
                  SET id_repartidor = :id_repartidor, estado_pedido = 'En Camino' 
                  WHERE id_pedido = :id_pedido AND estado_pedido != 'Cancelado'";

        $stmt = $this->conn->prepare($query);

        $this->id_repartidor = htmlspecialchars(strip_tags($this->id_repartidor));
        $this->id_pedido = htmlspecialchars(strip_tags($this->id_pedido));

        $stmt->bindParam(":id_repartidor", $this->id_repartidor);
        $stmt->bindParam(":id_pedido", $this->id_pedido);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 4. Actualizar el estado del pedido (ej. Preparando -> Entregado)
     */
    public function actualizarEstado() {
        $query = "UPDATE " . $this->table_name . " 
                  SET estado_pedido = :estado_pedido 
                  WHERE id_pedido = :id_pedido";

        $stmt = $this->conn->prepare($query);

        $this->estado_pedido = htmlspecialchars(strip_tags($this->estado_pedido));
        $this->id_pedido = htmlspecialchars(strip_tags($this->id_pedido));

        $stmt->bindParam(":estado_pedido", $this->estado_pedido);
        $stmt->bindParam(":id_pedido", $this->id_pedido);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 5. Obtener los pedidos activos de un Restaurante
     */
    public function obtenerActivosPorRestaurante($id_restaurante) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_restaurante = :id_restaurante 
                  AND estado_pedido NOT IN ('Entregado', 'Cancelado') 
                  ORDER BY fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $id_restaurante = htmlspecialchars(strip_tags($id_restaurante));
        $stmt->bindParam(":id_restaurante", $id_restaurante);
        $stmt->execute();

        return $stmt;
    }
}
?>