<?php

class DireccionUsuario {
    private $conn;
    private $table_name = "direcciones_usuario";

    public $id_direccion;
    public $id_usuario;
    public $alias;
    public $direccion_completa;
    public $referencias;
    public $latitud;
    public $longitud;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Guardar una nueva dirección
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_usuario, alias, direccion_completa, referencias, latitud, longitud) 
                  VALUES (:id_usuario, :alias, :direccion_completa, :referencias, :latitud, :longitud)";

        $stmt = $this->conn->prepare($query);

        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));
        $this->alias = htmlspecialchars(strip_tags($this->alias));
        $this->direccion_completa = htmlspecialchars(strip_tags($this->direccion_completa));
        $this->referencias = htmlspecialchars(strip_tags($this->referencias));

        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->bindParam(":alias", $this->alias);
        $stmt->bindParam(":direccion_completa", $this->direccion_completa);
        $stmt->bindParam(":referencias", $this->referencias);
        $stmt->bindParam(":latitud", $this->latitud);
        $stmt->bindParam(":longitud", $this->longitud);

        return $stmt->execute();
    }

    /**
     * Obtener todas las direcciones guardadas de un usuario
     */
    public function obtenerPorUsuario($id_usuario) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_usuario = :id_usuario ORDER BY alias ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Eliminar una dirección
     */
    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_direccion = :id_direccion AND id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_direccion", $this->id_direccion);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        return $stmt->execute();
    }
}