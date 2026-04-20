<?php

class CuentaBancaria {
    private $conn;
    private $table_name = "cuentas_bancarias";

    // Propiedades de la tabla
    public $id_cuenta;
    public $banco;
    public $cuenta_principal;
    public $fecha_registro;
    public $id_usuario;
    public $numero_cuenta;
    public $tipo_cuenta;
    public $titular_cuenta;
    public $token_pasarela;

    // Constructor con conexión a base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Crear una nueva cuenta bancaria
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (banco, cuenta_principal, id_usuario, numero_cuenta, tipo_cuenta, titular_cuenta, token_pasarela) 
                  VALUES (:banco, :cuenta_principal, :id_usuario, :numero_cuenta, :tipo_cuenta, :titular_cuenta, :token_pasarela)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->banco = htmlspecialchars(strip_tags($this->banco));
        $this->cuenta_principal = htmlspecialchars(strip_tags($this->cuenta_principal));
        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));
        $this->numero_cuenta = htmlspecialchars(strip_tags($this->numero_cuenta));
        $this->tipo_cuenta = htmlspecialchars(strip_tags($this->tipo_cuenta));
        $this->titular_cuenta = htmlspecialchars(strip_tags($this->titular_cuenta));
        $this->token_pasarela = htmlspecialchars(strip_tags($this->token_pasarela));

        // Vincular parámetros
        $stmt->bindParam(":banco", $this->banco);
        $stmt->bindParam(":cuenta_principal", $this->cuenta_principal);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->bindParam(":numero_cuenta", $this->numero_cuenta);
        $stmt->bindParam(":tipo_cuenta", $this->tipo_cuenta);
        $stmt->bindParam(":titular_cuenta", $this->titular_cuenta);
        $stmt->bindParam(":token_pasarela", $this->token_pasarela);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 2. Obtener todas las cuentas de un usuario específico
     */
    public function obtenerPorUsuario($id_usuario) {
        $query = "SELECT id_cuenta, banco, cuenta_principal, fecha_registro, numero_cuenta, tipo_cuenta, titular_cuenta, token_pasarela 
                  FROM " . $this->table_name . " 
                  WHERE id_usuario = :id_usuario 
                  ORDER BY cuenta_principal DESC, fecha_registro DESC";

        $stmt = $this->conn->prepare($query);
        
        $id_usuario = htmlspecialchars(strip_tags($id_usuario));
        $stmt->bindParam(":id_usuario", $id_usuario);
        
        $stmt->execute();
        return $stmt;
    }

    /**
     * 3. Obtener el detalle de UNA sola cuenta
     */
    public function obtenerPorId() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_cuenta = :id_cuenta AND id_usuario = :id_usuario 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        
        $this->id_cuenta = htmlspecialchars(strip_tags($this->id_cuenta));
        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));
        
        $stmt->bindParam(":id_cuenta", $this->id_cuenta);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->banco = $row['banco'];
            $this->cuenta_principal = $row['cuenta_principal'];
            $this->fecha_registro = $row['fecha_registro'];
            $this->numero_cuenta = $row['numero_cuenta'];
            $this->tipo_cuenta = $row['tipo_cuenta'];
            $this->titular_cuenta = $row['titular_cuenta'];
            $this->token_pasarela = $row['token_pasarela'];
            return true;
        }
        return false;
    }

    /**
     * 4. Actualizar información de la cuenta
     */
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET banco = :banco, 
                      cuenta_principal = :cuenta_principal, 
                      numero_cuenta = :numero_cuenta, 
                      tipo_cuenta = :tipo_cuenta, 
                      titular_cuenta = :titular_cuenta, 
                      token_pasarela = :token_pasarela 
                  WHERE id_cuenta = :id_cuenta AND id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($query);

        // Sanitizar
        $this->banco = htmlspecialchars(strip_tags($this->banco));
        $this->cuenta_principal = htmlspecialchars(strip_tags($this->cuenta_principal));
        $this->numero_cuenta = htmlspecialchars(strip_tags($this->numero_cuenta));
        $this->tipo_cuenta = htmlspecialchars(strip_tags($this->tipo_cuenta));
        $this->titular_cuenta = htmlspecialchars(strip_tags($this->titular_cuenta));
        $this->token_pasarela = htmlspecialchars(strip_tags($this->token_pasarela));
        $this->id_cuenta = htmlspecialchars(strip_tags($this->id_cuenta));
        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));

        // Vincular
        $stmt->bindParam(":banco", $this->banco);
        $stmt->bindParam(":cuenta_principal", $this->cuenta_principal);
        $stmt->bindParam(":numero_cuenta", $this->numero_cuenta);
        $stmt->bindParam(":tipo_cuenta", $this->tipo_cuenta);
        $stmt->bindParam(":titular_cuenta", $this->titular_cuenta);
        $stmt->bindParam(":token_pasarela", $this->token_pasarela);
        $stmt->bindParam(":id_cuenta", $this->id_cuenta);
        $stmt->bindParam(":id_usuario", $this->id_usuario);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 5. Eliminar una cuenta bancaria
     */
    public function eliminar() {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id_cuenta = :id_cuenta AND id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($query);

        $this->id_cuenta = htmlspecialchars(strip_tags($this->id_cuenta));
        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));

        $stmt->bindParam(":id_cuenta", $this->id_cuenta);
        $stmt->bindParam(":id_usuario", $this->id_usuario);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>