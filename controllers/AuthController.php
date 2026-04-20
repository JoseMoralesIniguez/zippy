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
    /**
 * POST: Registro de nuevos usuarios
 */
public function registro() {
    // 1. Leer los datos enviados (JSON)
    $data = json_decode(file_get_contents("php://input"));

    // 2. Validación básica de campos obligatorios
    if (!empty($data->nombre) && !empty($data->email) && !empty($data->password) && !empty($data->telefono)) {
        
        // 3. Preparar el modelo de Usuario
        $this->usuarioModel->nombre = $data->nombre;
        $this->usuarioModel->email = $data->email;
        $this->usuarioModel->telefono = $data->telefono;
        
        // Asignar rol por defecto si no se especifica (usualmente 'Cliente')
        $this->usuarioModel->rol = isset($data->rol) ? $data->rol : 'Cliente';

        // 4. Seguridad: Hashing de la contraseña
        // Nunca guardes contraseñas en texto plano. BCRYPT es el estándar actual.
        $this->usuarioModel->password_hash = password_hash($data->password, PASSWORD_BCRYPT);

        // 5. Intentar guardar en la base de datos
        // Nota: El modelo Usuario debe tener un método crear() que use estos datos.
        if ($this->usuarioModel->crear()) {
            http_response_code(201); // Created
            echo json_encode([
                "status" => "success", 
                "mensaje" => "¡Bienvenido a Zyppy! Tu cuenta ha sido creada exitosamente."
            ]);
        } else {
            http_response_code(503); // Service Unavailable
            echo json_encode([
                "status" => "error", 
                "mensaje" => "No se pudo completar el registro. El correo podría estar ya en uso."
            ]);
        }
    } else {
        http_response_code(400); // Bad Request
        echo json_encode([
            "status" => "error", 
            "mensaje" => "Datos incompletos. Se requiere nombre, email, teléfono y contraseña."
        ]);
    }
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