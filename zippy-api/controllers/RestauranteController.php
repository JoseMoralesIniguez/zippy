<?php
include_once '../config/Database.php';
include_once '../models/Restaurante.php';

class RestauranteController {
    private $db;
    private $restaurante;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->restaurante = new Restaurante($this->db);
    }

    public function listarRestaurantesAbiertos() {
        $stmt = $this->restaurante->obtenerAbiertos();
        $num = $stmt->rowCount();

        if($num > 0) {
            $restaurantes_arr = array();
            $restaurantes_arr["data"] = array(); // Empaquetamos en un nodo "data"

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Extraemos las variables de la fila actual
                extract($row);

                $restaurante_item = array(
                    "id" => $id_restaurante,
                    "nombre" => $nombre,
                    "direccion" => $direccion,
                    "coordenadas" => array(
                        "lat" => $latitud,
                        "lng" => $longitud
                    )
                );
                array_push($restaurantes_arr["data"], $restaurante_item);
            }

            // Respondemos con un código 200 (OK) y el JSON
            http_response_code(200);
            echo json_encode($restaurantes_arr);
        } else {
            // Si no hay restaurantes abiertos
            http_response_code(404);
            echo json_encode(array("mensaje" => "No se encontraron restaurantes abiertos en este momento."));
        }
    }
}
?>