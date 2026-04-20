<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/EvidenciaPedido.php';

class EvidenciaPedidoController {
    private $db;
    private $evidenciaModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro"; 
    // Carpeta donde se guardarán las fotos (asegúrate de crearla en tu servidor)
    private $directorio_subidas = "../uploads/evidencias/"; 

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->evidenciaModel = new EvidenciaPedido($this->db);

        // Crear el directorio si no existe
        if (!file_exists($this->directorio_subidas)) {
            mkdir($this->directorio_subidas, 0777, true);
        }
    }

    /**
     * Función auxiliar para validar el Token JWT.
     */
    private function validarAcceso() {
        $headers = apache_request_headers();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            try {
                $decoded = JWT::decode($token, new Key($this->secreto_jwt, 'HS256'));
                return $decoded->data->id_usuario; 
            } catch (Exception $e) {
                http_response_code(401);
                echo json_encode(["status" => "error", "mensaje" => "Token inválido o expirado."]);
                exit();
            }
        } else {
            http_response_code(401);
            echo json_encode(["status" => "error", "mensaje" => "Se requiere un Token de autorización."]);
            exit();
        }
    }

    /**
     * 1. Subir una nueva evidencia fotográfica (Requiere POST con form-data)
     */
    public function crear() {
        // Validamos autenticación
        $this->validarAcceso();

        // Al subir archivos, usamos $_POST en lugar del JSON body
        if (isset($_POST['id_pedido']) && isset($_POST['subido_por']) && isset($_FILES['foto'])) {
            
            $foto = $_FILES['foto'];

            // Validar que no haya errores en la subida
            if ($foto['error'] === UPLOAD_ERR_OK) {
                
                // Extraer extensión y validar tipo de archivo (solo imágenes)
                $extension = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
                $formatos_permitidos = array("jpg", "jpeg", "png", "webp");

                if (in_array($extension, $formatos_permitidos)) {
                    
                    // Crear un nombre de archivo único para evitar que se sobrescriban
                    // Ejemplo: pedido_15_167890123.jpg
                    $nombre_archivo_nuevo = "pedido_" . $_POST['id_pedido'] . "_" . time() . "." . $extension;
                    $ruta_destino = $this->directorio_subidas . $nombre_archivo_nuevo;

                    // Mover el archivo de la memoria temporal a nuestra carpeta
                    if (move_uploaded_file($foto['tmp_name'], $ruta_destino)) {
                        
                        // Si se guardó físicamente, ahora lo registramos en la Base de Datos
                        $this->evidenciaModel->id_pedido = $_POST['id_pedido'];
                        $this->evidenciaModel->subido_por = $_POST['subido_por'];
                        // Guardamos la ruta relativa para poder llamarla desde el frontend
                        $this->evidenciaModel->url_foto = "uploads/evidencias/" . $nombre_archivo_nuevo; 
                        $this->evidenciaModel->comentario_evidencia = isset($_POST['comentario_evidencia']) ? $_POST['comentario_evidencia'] : null;

                        if ($this->evidenciaModel->crear()) {
                            http_response_code(201);
                            echo json_encode(["status" => "success", "mensaje" => "Evidencia subida correctamente.", "url" => $this->evidenciaModel->url_foto]);
                        } else {
                            // Si falló la BD, borramos la foto que acabamos de subir por seguridad
                            unlink($ruta_destino);
                            http_response_code(503);
                            echo json_encode(["status" => "error", "mensaje" => "Error al guardar en la base de datos."]);
                        }
                    } else {
                        http_response_code(500);
                        echo json_encode(["status" => "error", "mensaje" => "Error al mover el archivo en el servidor."]);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "mensaje" => "Formato no permitido. Solo JPG, JPEG, PNG y WEBP."]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["status" => "error", "mensaje" => "El archivo superó el límite de peso o está corrupto."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Faltan datos. Se requiere id_pedido, subido_por y el archivo 'foto'."]);
        }
    }

    /**
     * 2. Obtener las evidencias de un pedido (Requiere GET)
     */
    public function listarPorPedido() {
        $this->validarAcceso();
        $id_pedido = isset($_GET['id_pedido']) ? $_GET['id_pedido'] : null;

        if ($id_pedido) {
            $stmt = $this->evidenciaModel->obtenerPorPedido($id_pedido);
            
            if ($stmt->rowCount() > 0) {
                $evidencias_arr = array();
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    array_push($evidencias_arr, $row);
                }

                http_response_code(200);
                echo json_encode(["status" => "success", "data" => $evidencias_arr]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "info", "mensaje" => "No hay evidencias para este pedido."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Falta el id_pedido."]);
        }
    }

    /**
     * 3. Eliminar una evidencia (Requiere DELETE)
     */
    public function eliminar() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_evidencia)) {
            $this->evidenciaModel->id_evidencia = $data->id_evidencia;

            // 1. Primero buscamos la evidencia en la BD para obtener la URL del archivo
            if ($this->evidenciaModel->obtenerPorId()) {
                
                $ruta_archivo = "../" . $this->evidenciaModel->url_foto;

                // 2. Eliminamos el registro de la Base de Datos
                if ($this->evidenciaModel->eliminar()) {
                    
                    // 3. Si se borró de la BD, eliminamos el archivo físico usando unlink()
                    if (file_exists($ruta_archivo)) {
                        unlink($ruta_archivo);
                    }

                    http_response_code(200);
                    echo json_encode(["status" => "success", "mensaje" => "Evidencia eliminada de la base de datos y del servidor."]);
                } else {
                    http_response_code(503);
                    echo json_encode(["status" => "error", "mensaje" => "No se pudo eliminar el registro de la base de datos."]);
                }
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "mensaje" => "La evidencia no existe."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Falta el id_evidencia."]);
        }
    }
}
?>