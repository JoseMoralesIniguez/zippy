<?php

class DatosRepartidor {
    private $conn;
    private $table_name = "datos_repartidor";

    // Propiedades de la tabla
    public $id_usuario; // Actúa como Primary Key y Foreign Key al mismo tiempo
    public $estatus_conexion;
    public $latitud_actual;
    public $longitud_actual;
    public $tipo_vehiculo;

    // Constructor con conexión a base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Crear el perfil de repartidor para un usuario
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_usuario, estatus_conexion, latitud_actual, longitud_actual, tipo_vehiculo) 
                  VALUES (:id_usuario, :estatus_conexion, :latitud_actual, :longitud_actual, :tipo_vehiculo)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));
        $this->estatus_conexion = htmlspecialchars(strip_tags($this->estatus_conexion));
        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        
        // Las coordenadas se limpian pero se debe permitir el punto decimal y el signo menos
        $this->latitud_actual = htmlspecialchars(strip_tags($this->latitud_actual));
        $this->longitud_actual = htmlspecialchars(strip_tags($this->longitud_actual));

        // Vincular parámetros
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->bindParam(":estatus_conexion", $this->estatus_conexion);
        $stmt->bindParam(":latitud_actual", $this->latitud_actual);
        $stmt->bindParam(":longitud_actual", $this->longitud_actual);
        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 2. Obtener los datos del repartidor por su ID de usuario
     */
    public function obtenerPorId() {
        $query = "SELECT id_usuario, estatus_conexion, latitud_actual, longitud_actual, tipo_vehiculo 
                  FROM " . $this->table_name . " 
                  WHERE id_usuario = :id_usuario 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        
        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->estatus_conexion = $row['estatus_conexion'];
            $this->latitud_actual = $row['latitud_actual'];
            $this->longitud_actual = $row['longitud_actual'];
            $this->tipo_vehiculo = $row['tipo_vehiculo'];
            return true;
        }
        return false;
    }

    /**
     * 3. Actualizar información general del repartidor (ej. cambia de bici a moto)
     */
    public function actualizarPerfil() {
        $query = "UPDATE " . $this->table_name . " 
                  SET tipo_vehiculo = :tipo_vehiculo 
                  WHERE id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($query);

        $this->tipo_vehiculo = htmlspecialchars(strip_tags($this->tipo_vehiculo));
        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));

        $stmt->bindParam(":tipo_vehiculo", $this->tipo_vehiculo);
        $stmt->bindParam(":id_usuario", $this->id_usuario);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 4. Actualizar estado y ubicación en tiempo real (Optimizado para ping GPS)
     */
    public function actualizarUbicacionYEstado() {
        $query = "UPDATE " . $this->table_name . " 
                  SET estatus_conexion = :estatus_conexion, 
                      latitud_actual = :latitud_actual, 
                      longitud_actual = :longitud_actual 
                  WHERE id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($query);

        $this->estatus_conexion = htmlspecialchars(strip_tags($this->estatus_conexion));
        $this->latitud_actual = htmlspecialchars(strip_tags($this->latitud_actual));
        $this->longitud_actual = htmlspecialchars(strip_tags($this->longitud_actual));
        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));

        $stmt->bindParam(":estatus_conexion", $this->estatus_conexion);
        $stmt->bindParam(":latitud_actual", $this->latitud_actual);
        $stmt->bindParam(":longitud_actual", $this->longitud_actual);
        $stmt->bindParam(":id_usuario", $this->id_usuario);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 5. Eliminar los datos de repartidor (si el usuario deja de serlo)
     */
    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($query);

        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));
        $stmt->bindParam(":id_usuario", $this->id_usuario);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>