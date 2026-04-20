<?php

class Producto {
    private $conn;
    private $table_name = "productos";

    // Propiedades de la tabla
    public $id_producto;
    public $id_categoria;
    public $nombre;
    public $descripcion; // Nullable
    public $precio;
    public $disponible; // Nullable, pero lo ideal es tratarlo como booleano (0 o 1)

    // Constructor con conexión a base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Crear un nuevo producto
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_categoria, nombre, descripcion, precio, disponible) 
                  VALUES (:id_categoria, :nombre, :descripcion, :precio, :disponible)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos obligatorios
        $this->id_categoria = htmlspecialchars(strip_tags($this->id_categoria));
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->precio = htmlspecialchars(strip_tags($this->precio));
        
        // Manejar nulos o valores por defecto
        if ($this->descripcion !== null) {
            $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        }
        
        // Si no se especifica, por defecto estará disponible (1)
        $this->disponible = isset($this->disponible) ? $this->disponible : 1;

        // Vincular parámetros
        $stmt->bindParam(":id_categoria", $this->id_categoria);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":precio", $this->precio);
        $stmt->bindParam(":disponible", $this->disponible);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 2. Obtener todos los productos de una categoría específica
     * Ideal para mostrar el menú agrupado en la app cliente.
     */
    public function obtenerPorCategoria($id_categoria, $solo_disponibles = false) {
        // Si es para el cliente, solo mostramos los disponibles. Si es para el admin, mostramos todos.
        $condicion_disponible = $solo_disponibles ? " AND disponible = 1" : "";
        
        $query = "SELECT id_producto, id_categoria, nombre, descripcion, precio, disponible 
                  FROM " . $this->table_name . " 
                  WHERE id_categoria = :id_categoria" . $condicion_disponible . "
                  ORDER BY nombre ASC";

        $stmt = $this->conn->prepare($query);
        
        $id_categoria = htmlspecialchars(strip_tags($id_categoria));
        $stmt->bindParam(":id_categoria", $id_categoria);
        
        $stmt->execute();
        return $stmt;
    }

    /**
     * 3. Obtener el detalle de un solo producto
     */
    public function obtenerPorId() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_producto = :id_producto 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        
        $this->id_producto = htmlspecialchars(strip_tags($this->id_producto));
        $stmt->bindParam(":id_producto", $this->id_producto);
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_categoria = $row['id_categoria'];
            $this->nombre = $row['nombre'];
            $this->descripcion = $row['descripcion'];
            $this->precio = $row['precio'];
            $this->disponible = $row['disponible'];
            return true;
        }
        return false;
    }

    /**
     * 4. Actualizar toda la información de un producto
     */
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET id_categoria = :id_categoria, 
                      nombre = :nombre, 
                      descripcion = :descripcion, 
                      precio = :precio, 
                      disponible = :disponible 
                  WHERE id_producto = :id_producto";

        $stmt = $this->conn->prepare($query);

        $this->id_categoria = htmlspecialchars(strip_tags($this->id_categoria));
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->precio = htmlspecialchars(strip_tags($this->precio));
        $this->id_producto = htmlspecialchars(strip_tags($this->id_producto));
        
        if ($this->descripcion !== null) {
            $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        }

        $stmt->bindParam(":id_categoria", $this->id_categoria);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":precio", $this->precio);
        $stmt->bindParam(":disponible", $this->disponible);
        $stmt->bindParam(":id_producto", $this->id_producto);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 5. Método rápido para activar/desactivar un producto sin tocar el resto de datos
     */
    public function cambiarDisponibilidad() {
        $query = "UPDATE " . $this->table_name . " 
                  SET disponible = :disponible 
                  WHERE id_producto = :id_producto";

        $stmt = $this->conn->prepare($query);

        $this->disponible = htmlspecialchars(strip_tags($this->disponible));
        $this->id_producto = htmlspecialchars(strip_tags($this->id_producto));

        $stmt->bindParam(":disponible", $this->disponible);
        $stmt->bindParam(":id_producto", $this->id_producto);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 6. Eliminar un producto
     */
    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id_producto = :id_producto";

        $stmt = $this->conn->prepare($query);

        $this->id_producto = htmlspecialchars(strip_tags($this->id_producto));
        $stmt->bindParam(":id_producto", $this->id_producto);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>