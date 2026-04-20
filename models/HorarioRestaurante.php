<?php
class HorarioRestaurante {
    private $conn;
    private $table_name = "horarios_restaurante";

    public $id_restaurante;
    public $dia_semana;
    public $hora_apertura;
    public $hora_cierre;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Guarda o actualiza el horario de un día específico
     */
    public function guardar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_restaurante, dia_semana, hora_apertura, hora_cierre) 
                  VALUES (:id_res, :dia, :apertura, :cierre)
                  ON DUPLICATE KEY UPDATE hora_apertura = :apertura, hora_cierre = :cierre";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_res", $this->id_restaurante);
        $stmt->bindParam(":dia", $this->dia_semana);
        $stmt->bindParam(":apertura", $this->hora_apertura);
        $stmt->bindParam(":cierre", $this->hora_cierre);

        return $stmt->execute();
    }

    /**
     * Verifica si el restaurante está abierto actualmente
     */
    public function estaAbiertoAhora($id_restaurante) {
        // Obtenemos día actual (1-7) y hora actual según la zona horaria del servidor
        $dia_actual = date('N'); 
        $hora_actual = date('H:i:s');

        $query = "SELECT 1 FROM " . $this->table_name . " 
                  WHERE id_restaurante = :id_res 
                  AND dia_semana = :dia 
                  AND :hora BETWEEN hora_apertura AND hora_cierre 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_res", $id_restaurante);
        $stmt->bindParam(":dia", $dia_actual);
        $stmt->bindParam(":hora", $hora_actual);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}