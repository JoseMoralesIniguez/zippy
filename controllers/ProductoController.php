<?php
require_once '../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

include_once '../config/Database.php';
include_once '../models/Producto.php';
include_once '../models/HorarioRestaurante.php';

// Opcional: Podrías incluir el modelo de Categoría aquí si quieres validar 
// que la categoría existe antes de crear un producto.

class ProductoController {
    private $db;
    private $productoModel;
    private $secreto_jwt = "Zippy_Super_Secreto_2026_!@#_ExtraLargo_Y_Seguro";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->productoModel = new Producto($this->db);
    }

    /**
     * Valida el acceso mediante JWT.
     * En un entorno real, la creación/edición de productos debería estar restringida
     * a usuarios con rol de "Restaurante" o "Administrador".
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
            echo json_encode(["status" => "error", "mensaje" => "Se requiere autorización."]);
            exit();
        }
    }

    /**
     * 1. Crear un nuevo producto (POST)
     */
    public function crear() {
        $this->validarAcceso(); // Solo usuarios autorizados deberían poder crear
        $data = json_decode(file_get_contents("php://input"));
    
        // Dentro de PedidoController -> crear()
        $horario = new HorarioRestaurante($this->db);
        if (!$horario->estaAbiertoAhora($data->id_restaurante)) {
            http_response_code(403);
            echo json_encode(["status" => "error", "mensaje" => "El restaurante está cerrado por el momento."]);
            exit();
        }
        // Validamos campos obligatorios
        if (!empty($data->id_categoria) && !empty($data->nombre) && isset($data->precio)) {
            
            $this->productoModel->id_categoria = $data->id_categoria;
            $this->productoModel->nombre = $data->nombre;
            $this->productoModel->precio = $data->precio;
            
            
            // Campos opcionales
            $this->productoModel->descripcion = isset($data->descripcion) ? $data->descripcion : null;
            $this->productoModel->disponible = isset($data->disponible) ? $data->disponible : 1; // 1 (Activo) por defecto

            if ($this->productoModel->crear()) {
                http_response_code(201);
                echo json_encode(["status" => "success", "mensaje" => "Producto creado exitosamente."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo crear el producto."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Datos incompletos. Se requiere id_categoria, nombre y precio."]);
        }
    }

    /**
     * 2. Obtener productos de una categoría (GET)
     * Este endpoint es público o al menos accesible para cualquier cliente,
     * ya que necesitan ver el menú para comprar.
     */
    public function listarPorCategoria() {
        // Nota: Para ver el menú no siempre se requiere JWT, depende de si tu app 
        // permite ver restaurantes sin iniciar sesión. Si quieres forzar login, 
        // descomenta la siguiente línea:
        // $this->validarAcceso();

        $id_categoria = isset($_GET['id_categoria']) ? $_GET['id_categoria'] : die();
        
        // Verificamos si la petición viene del cliente (solo quiere ver lo disponible)
        // o del panel de administración (quiere ver todo).
        // Podemos usar un parámetro extra en la URL: ?vista=admin
        $vista_admin = (isset($_GET['vista']) && $_GET['vista'] === 'admin') ? true : false;
        
        // Si no es admin, forzamos $solo_disponibles a true
        $solo_disponibles = !$vista_admin; 

        $stmt = $this->productoModel->obtenerPorCategoria($id_categoria, $solo_disponibles);
        $num = $stmt->rowCount();

        if ($num > 0) {
            $productos_arr = array();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Castear valores para que el JSON sea más limpio (números como números, no strings)
                $producto_item = array(
                    "id_producto" => (int)$row['id_producto'],
                    "id_categoria" => (int)$row['id_categoria'],
                    "nombre" => $row['nombre'],
                    "descripcion" => $row['descripcion'],
                    "precio" => (float)$row['precio'],
                    "disponible" => (bool)$row['disponible'] // Transforma el 0/1 de MySQL a false/true en JSON
                );
                array_push($productos_arr, $producto_item);
            }

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $productos_arr]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "info", "mensaje" => "No hay productos en esta categoría."]);
        }
    }

    /**
     * 3. Actualizar información completa de un producto (PUT)
     */
    public function actualizar() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_producto) && !empty($data->id_categoria) && !empty($data->nombre) && isset($data->precio)) {
            
            $this->productoModel->id_producto = $data->id_producto;
            $this->productoModel->id_categoria = $data->id_categoria;
            $this->productoModel->nombre = $data->nombre;
            $this->productoModel->precio = $data->precio;
            $this->productoModel->descripcion = isset($data->descripcion) ? $data->descripcion : null;
            $this->productoModel->disponible = isset($data->disponible) ? $data->disponible : 1;

            if ($this->productoModel->actualizar()) {
                http_response_code(200);
                echo json_encode(["status" => "success", "mensaje" => "Producto actualizado."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo actualizar el producto."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Datos incompletos para actualizar."]);
        }
    }

    /**
     * 4. Activar o Desactivar un producto rápidamente (PATCH)
     * Ideal para el botón tipo "switch" en el panel del restaurante.
     */
    public function cambiarDisponibilidad() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_producto) && isset($data->disponible)) {
            
            $this->productoModel->id_producto = $data->id_producto;
            // Aseguramos que solo sea 1 o 0
            $this->productoModel->disponible = $data->disponible ? 1 : 0; 

            if ($this->productoModel->cambiarDisponibilidad()) {
                $estado_texto = $this->productoModel->disponible ? "activado" : "desactivado";
                http_response_code(200);
                echo json_encode(["status" => "success", "mensaje" => "Producto " . $estado_texto . "."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo cambiar la disponibilidad."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Se requiere id_producto y el estado disponible (true/false)."]);
        }
    }

    /**
     * 5. Eliminar un producto (DELETE)
     */
    public function eliminar() {
        $this->validarAcceso();
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id_producto)) {
            $this->productoModel->id_producto = $data->id_producto;

            if ($this->productoModel->eliminar()) {
                http_response_code(200);
                echo json_encode(["status" => "success", "mensaje" => "Producto eliminado permanentemente."]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "mensaje" => "No se pudo eliminar el producto."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "mensaje" => "Falta el id_producto."]);
        }
    }
}
?>