<?php
// Incluimos el autoloader de Composer para usar la librería JWT
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;

include_once '../config/Database.php';
include_once '../models/Usuario.php';

class AuthController {
    private $db;
    private $usuarioModel;
    
    // ESTA ES TU LLAVE MAESTRA. ¡NUNCA LA COMPARTAS! 
    // En producción, esto debe venir de un archivo .env
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->usuarioModel = new Usuario($this->db);
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"));

        // Validar que mandaron email y password
        if (!empty($data->email) && !empty($data->password)) {
            
            // 1. Buscar al usuario en la base de datos
            $usuario_db = $this->usuarioModel->obtenerPorEmail($data->email);

            // 2. Verificar si el usuario existe Y si la contraseña coincide
            // Nota: password_verify compara el texto plano con el hash seguro de MySQL
            if ($usuario_db && password_verify($data->password, $usuario_db['password_hash'])) {
                
                // 3. Crear el "Payload" (La información que llevará el Gafete)
                $tiempo_emision = time();
                $tiempo_expiracion = $tiempo_emision + (60 * 60 * 24 * 30); // El token dura 30 días
                
                $payload = array(
                    "iat" => $tiempo_emision,       // Issued At: Cuándo se creó
                    "exp" => $tiempo_expiracion,    // Expiration time: Cuándo caduca
                    "data" => array(                // La info pública del usuario
                        "id_usuario" => $usuario_db['id_usuario'],
                        "nombre" => $usuario_db['nombre'],
                        "rol" => $usuario_db['rol']
                    )
                );

                // 4. Firmar el Token
                $jwt = JWT::encode($payload, $this->secreto_jwt, 'HS256');

                // 5. Devolver el Token a la App Móvil
                http_response_code(200);
                echo json_encode(array(
                    "status" => "success",
                    "mensaje" => "Inicio de sesión exitoso.",
                    "token" => $jwt,
                    "rol" => $usuario_db['rol'] // Útil para que la app sepa qué pantalla mostrar
                ));

            } else {
                // Contraseña incorrecta o correo no existe
                http_response_code(401); // 401 Unauthorized
                echo json_encode(array("status" => "error", "mensaje" => "Correo o contraseña incorrectos."));
            }
        } else {
            http_response_code(400); // 400 Bad Request
            echo json_encode(array("status" => "error", "mensaje" => "Debe ingresar correo y contraseña."));
        }
    }
}
?>