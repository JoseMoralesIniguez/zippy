<?php
class Usuario {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Busca al usuario por su email para intentar iniciar sesión
    public function obtenerPorEmail($email) {
        $query = "SELECT id_usuario, nombre, email, password_hash, rol 
                  FROM Usuarios 
                  WHERE email = :email LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>