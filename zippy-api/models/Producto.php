<?php
class Producto {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Método para obtener el precio real y saber si está disponible
    public function obtenerPrecioYDisponibilidad($id_producto) {
        $query = "SELECT precio, disponible FROM Productos WHERE id_producto = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_producto);
        $stmt->execute();

        // Retornamos la fila como un arreglo asociativo
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>