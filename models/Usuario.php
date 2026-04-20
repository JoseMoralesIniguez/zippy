<?php
class Usuario {
    private $conn;
    private $table_name = "usuarios"; // Nombre de la tabla en minúsculas según tu SQL

    // Propiedades del usuario
    public $id_usuario;
    public $nombre;
    public $telefono;
    public $email;
    public $password_hash;
    public $rol;
    public $fcm_token; // Nueva propiedad para notificaciones push

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Obtener el token FCM de un usuario específico
     * Se usa para saber a qué dispositivo enviar la notificación push.
     */
    public function obtenerFCMToken($id_usuario) {
        $query = "SELECT fcm_token FROM " . $this->table_name . " WHERE id_usuario = :id_usuario LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Retornamos el token si existe, de lo contrario null
        return $row ? $row['fcm_token'] : null;
    }

    /**
     * 2. Actualizar el FCM Token (Indispensable)
     * La App móvil debe llamar a este método cada vez que el token cambie o el usuario inicie sesión.
     */
    public function actualizarFCMToken($id_usuario, $token) {
        $query = "UPDATE " . $this->table_name . " SET fcm_token = :token WHERE id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':id_usuario', $id_usuario);

        return $stmt->execute();
    }

    /**
     * Busca al usuario por su email para intentar iniciar sesión
     */
    public function obtenerPorEmail($email) {
        $query = "SELECT id_usuario, nombre, email, password_hash, rol 
                  FROM " . $this->table_name . " 
                  WHERE email = :email LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>