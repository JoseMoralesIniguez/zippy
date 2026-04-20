<?php

class Restaurante {
    private $conn;
    private $table_name = "restaurantes";

    // Propiedades de la tabla
    public $id_restaurante;
    public $nombre;
    public $direccion;
    public $latitud;
    public $longitud;
    public $abierto; // TINYINT (1 = Sí, 0 = No)
    public $porcentaje_comision;
    public $fecha_registro;

    // Constructor con conexión a base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Crear un nuevo restaurante en la plataforma
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nombre, direccion, latitud, longitud, abierto, porcentaje_comision) 
                  VALUES (:nombre, :direccion, :latitud, :longitud, :abierto, :porcentaje_comision)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos obligatorios
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->direccion = htmlspecialchars(strip_tags($this->direccion));
        $this->latitud = htmlspecialchars(strip_tags($this->latitud));
        $this->longitud = htmlspecialchars(strip_tags($this->longitud));
        
        // Manejar valores por defecto
        // Si no se especifica, nace abierto (1) y con la comisión por defecto (20.00)
        $this->abierto = isset($this->abierto) ? $this->abierto : 1;
        $this->porcentaje_comision = isset($this->porcentaje_comision) ? $this->porcentaje_comision : 20.00;

        // Vincular parámetros
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":latitud", $this->latitud);
        $stmt->bindParam(":longitud", $this->longitud);
        $stmt->bindParam(":abierto", $this->abierto);
        $stmt->bindParam(":porcentaje_comision", $this->porcentaje_comision);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 2. Obtener lista de restaurantes (Feed de la App)
     * Parámetro opcional para mostrar solo los que están abiertos.
     */
    public function obtenerTodos($solo_abiertos = true) {
        $condicion = $solo_abiertos ? " WHERE abierto = 1" : "";
        
        $query = "SELECT id_restaurante, nombre, direccion, latitud, longitud, abierto, porcentaje_comision 
                  FROM " . $this->table_name . $condicion . " 
                  ORDER BY nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * 3. Obtener el detalle de un restaurante específico
     */
    public function obtenerPorId() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_restaurante = :id_restaurante 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        
        $this->id_restaurante = htmlspecialchars(strip_tags($this->id_restaurante));
        $stmt->bindParam(":id_restaurante", $this->id_restaurante);
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->nombre = $row['nombre'];
            $this->direccion = $row['direccion'];
            $this->latitud = $row['latitud'];
            $this->longitud = $row['longitud'];
            $this->abierto = $row['abierto'];
            $this->porcentaje_comision = $row['porcentaje_comision'];
            $this->fecha_registro = $row['fecha_registro'];
            return true;
        }
        return false;
    }

    /**
     * 4. Actualizar la información del restaurante
     */
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre = :nombre, 
                      direccion = :direccion, 
                      latitud = :latitud, 
                      longitud = :longitud, 
                      porcentaje_comision = :porcentaje_comision 
                  WHERE id_restaurante = :id_restaurante";

        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->direccion = htmlspecialchars(strip_tags($this->direccion));
        $this->latitud = htmlspecialchars(strip_tags($this->latitud));
        $this->longitud = htmlspecialchars(strip_tags($this->longitud));
        $this->porcentaje_comision = htmlspecialchars(strip_tags($this->porcentaje_comision));
        $this->id_restaurante = htmlspecialchars(strip_tags($this->id_restaurante));

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":latitud", $this->latitud);
        $stmt->bindParam(":longitud", $this->longitud);
        $stmt->bindParam(":porcentaje_comision", $this->porcentaje_comision);
        $stmt->bindParam(":id_restaurante", $this->id_restaurante);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 5. Abrir o Cerrar el restaurante rápidamente (Switch)
     */
    public function cambiarEstadoOperativo() {
        $query = "UPDATE " . $this->table_name . " 
                  SET abierto = :abierto 
                  WHERE id_restaurante = :id_restaurante";

        $stmt = $this->conn->prepare($query);

        $this->abierto = htmlspecialchars(strip_tags($this->abierto));
        $this->id_restaurante = htmlspecialchars(strip_tags($this->id_restaurante));

        $stmt->bindParam(":abierto", $this->abierto);
        $stmt->bindParam(":id_restaurante", $this->id_restaurante);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>