<?php
class AdministradorRestaurante {
    private $conn;
    private $table_name = "administradores_restaurante";

    // Propiedades de la tabla
    public $id_restaurante;
    public $id_usuario;

    // Constructor que recibe la conexión a la base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Asignar un usuario como administrador de un restaurante
     */
    public function asignar($id_usuario, $id_restaurante) {
        $query = "INSERT INTO " . $this->table_name . " (id_usuario, id_restaurante) VALUES (:id_usuario, :id_restaurante)";
        
        $stmt = $this->conn->prepare($query);

        // Sanitizar
        $id_usuario = htmlspecialchars(strip_tags($id_usuario));
        $id_restaurante = htmlspecialchars(strip_tags($id_restaurante));

        // Vincular parámetros
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->bindParam(":id_restaurante", $id_restaurante);

        try {
            if($stmt->execute()) {
                return true;
            }
        } catch(PDOException $e) {
            // Manejar error de clave duplicada (si el usuario ya es admin de ese restaurante)
            if ($e->getCode() == 23000) {
                return false; // Ya está asignado
            }
            throw $e;
        }
        return false;
    }

    /**
     * 2. Verificar si un usuario tiene permisos sobre un restaurante específico
     * (¡Súper útil para proteger tus endpoints!)
     */
    public function verificarPermiso($id_usuario, $id_restaurante) {
        $query = "SELECT 1 FROM " . $this->table_name . " 
                  WHERE id_usuario = :id_usuario AND id_restaurante = :id_restaurante 
                  LIMIT 1";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->bindParam(":id_restaurante", $id_restaurante);
        $stmt->execute();

        // Si devuelve al menos una fila, significa que sí es administrador
        return $stmt->rowCount() > 0;
    }

    /**
     * 3. Obtener todos los restaurantes que administra un usuario
     */
    public function obtenerRestaurantesDelUsuario($id_usuario) {
        // Hacemos un JOIN con la tabla de restaurantes para traer los datos completos
        $query = "SELECT r.* FROM " . $this->table_name . " ar
                  JOIN restaurantes r ON ar.id_restaurante = r.id
                  WHERE ar.id_usuario = :id_usuario";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->execute();

        return $stmt;
    }

    /**
     * 4. Remover el acceso de un administrador a un restaurante
     */
    public function remover($id_usuario, $id_restaurante) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id_usuario = :id_usuario AND id_restaurante = :id_restaurante";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->bindParam(":id_restaurante", $id_restaurante);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>