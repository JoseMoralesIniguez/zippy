<?php
class Categoria {
    private $conn;
    private $table_name = "categorias";

    // Propiedades de la tabla
    public $id_categoria;
    public $id_restaurante;
    public $nombre_categoria;

    // Constructor con conexión a base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Crear una nueva categoría para un restaurante
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_restaurante, nombre_categoria) 
                  VALUES (:id_restaurante, :nombre_categoria)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->id_restaurante = htmlspecialchars(strip_tags($this->id_restaurante));
        $this->nombre_categoria = htmlspecialchars(strip_tags($this->nombre_categoria));

        // Vincular parámetros
        $stmt->bindParam(":id_restaurante", $this->id_restaurante);
        $stmt->bindParam(":nombre_categoria", $this->nombre_categoria);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 2. Obtener TODAS las categorías de un restaurante específico
     */
    public function obtenerPorRestaurante($id_restaurante) {
        $query = "SELECT id_categoria, id_restaurante, nombre_categoria 
                  FROM " . $this->table_name . " 
                  WHERE id_restaurante = :id_restaurante 
                  ORDER BY nombre_categoria ASC";

        $stmt = $this->conn->prepare($query);
        
        // Sanitizar y vincular
        $id_restaurante = htmlspecialchars(strip_tags($id_restaurante));
        $stmt->bindParam(":id_restaurante", $id_restaurante);
        
        $stmt->execute();

        return $stmt;
    }

    /**
     * 3. Obtener el detalle de UNA sola categoría (útil para editar)
     */
    public function obtenerPorId() {
        $query = "SELECT id_categoria, id_restaurante, nombre_categoria 
                  FROM " . $this->table_name . " 
                  WHERE id_categoria = :id_categoria 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_categoria", $this->id_categoria);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_restaurante = $row['id_restaurante'];
            $this->nombre_categoria = $row['nombre_categoria'];
            return true;
        }
        return false;
    }

    /**
     * 4. Actualizar el nombre de una categoría
     */
    public function actualizar() {
        // Exigimos el id_restaurante en el WHERE como medida de seguridad extra
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre_categoria = :nombre_categoria 
                  WHERE id_categoria = :id_categoria AND id_restaurante = :id_restaurante";

        $stmt = $this->conn->prepare($query);

        // Sanitizar
        $this->nombre_categoria = htmlspecialchars(strip_tags($this->nombre_categoria));
        $this->id_categoria = htmlspecialchars(strip_tags($this->id_categoria));
        $this->id_restaurante = htmlspecialchars(strip_tags($this->id_restaurante));

        // Vincular
        $stmt->bindParam(":nombre_categoria", $this->nombre_categoria);
        $stmt->bindParam(":id_categoria", $this->id_categoria);
        $stmt->bindParam(":id_restaurante", $this->id_restaurante);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 5. Eliminar una categoría
     */
    public function eliminar() {
        // De nuevo, incluimos el id_restaurante por seguridad
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id_categoria = :id_categoria AND id_restaurante = :id_restaurante";

        $stmt = $this->conn->prepare($query);

        $this->id_categoria = htmlspecialchars(strip_tags($this->id_categoria));
        $this->id_restaurante = htmlspecialchars(strip_tags($this->id_restaurante));

        $stmt->bindParam(":id_categoria", $this->id_categoria);
        $stmt->bindParam(":id_restaurante", $this->id_restaurante);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>