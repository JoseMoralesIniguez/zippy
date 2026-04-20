<?php
class Database {
    private $host = "localhost";
    private $db_name = "zippy";
    private $username = "root"; // Cambia esto por tu usuario de MySQL
    private $password = "";     // Cambia esto por tu contraseña
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // Usamos PDO porque previene inyecciones SQL
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8"); // Para aceptar acentos y ñ
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>