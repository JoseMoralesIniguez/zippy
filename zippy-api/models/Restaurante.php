<?php
class Restaurante {
    private $conn;
    private $table_name = "Restaurantes";

    // Propiedades del restaurante
    public $id_restaurante;
    public $nombre;
    public $direccion;
    public $latitud;
    public $longitud;
    public $abierto;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Método para obtener los restaurantes que están abiertos
    public function obtenerAbiertos() {
        $query = "SELECT id_restaurante, nombre, direccion, latitud, longitud 
                  FROM " . $this->table_name . " 
                  WHERE abierto = TRUE";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>