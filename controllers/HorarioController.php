<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/HorarioRestaurante.php';

class HorarioController {
    private $db;
    private $horarioModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->horarioModel = new HorarioRestaurante($this->db);
    }

    public function configurar() {
        // Aquí deberías validar que el usuario tenga rol de Admin o sea dueño del restaurante
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_restaurante) && !empty($data->horarios)) {
            foreach ($data->horarios as $h) {
                $this->horarioModel->id_restaurante = $data->id_restaurante;
                $this->horarioModel->dia_semana = $h->dia;
                $this->horarioModel->hora_apertura = $h->apertura;
                $this->horarioModel->hora_cierre = $h->cierre;
                $this->horarioModel->guardar();
            }
            echo json_encode(["status" => "success", "mensaje" => "Horarios actualizados."]);
        }
    }
}