<?php

class FinanzasPedido {
    private $conn;
    private $table_name = "finanzas_pedido";

    // Propiedades de la tabla
    public $id_finanza;
    public $id_pedido; // Único (UNI)
    public $subtotal_comida;
    public $tarifa_envio_cobrada;
    public $porcentaje_aplicado;
    public $comision_restaurante;
    public $pago_neto_restaurante;
    public $pago_neto_repartidor;
    public $ganancia_neta_zippy;
    public $id_liquidacion_restaurante; // Nullable
    public $id_liquidacion_repartidor; // Nullable
    public $fecha_calculo;

    // Constructor con conexión a base de datos
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * 1. Crear el registro financiero del pedido
     * Esto suele ejecutarse justo cuando el pedido se marca como "Completado" o "Entregado"
     */
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (id_pedido, subtotal_comida, tarifa_envio_cobrada, porcentaje_aplicado, 
                   comision_restaurante, pago_neto_restaurante, pago_neto_repartidor, ganancia_neta_zippy) 
                  VALUES 
                  (:id_pedido, :subtotal_comida, :tarifa_envio_cobrada, :porcentaje_aplicado, 
                   :comision_restaurante, :pago_neto_restaurante, :pago_neto_repartidor, :ganancia_neta_zippy)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar datos
        $this->id_pedido = htmlspecialchars(strip_tags($this->id_pedido));
        $this->subtotal_comida = htmlspecialchars(strip_tags($this->subtotal_comida));
        $this->tarifa_envio_cobrada = htmlspecialchars(strip_tags($this->tarifa_envio_cobrada));
        $this->porcentaje_aplicado = htmlspecialchars(strip_tags($this->porcentaje_aplicado));
        $this->comision_restaurante = htmlspecialchars(strip_tags($this->comision_restaurante));
        $this->pago_neto_restaurante = htmlspecialchars(strip_tags($this->pago_neto_restaurante));
        $this->pago_neto_repartidor = htmlspecialchars(strip_tags($this->pago_neto_repartidor));
        $this->ganancia_neta_zippy = htmlspecialchars(strip_tags($this->ganancia_neta_zippy));

        // Vincular parámetros
        $stmt->bindParam(":id_pedido", $this->id_pedido);
        $stmt->bindParam(":subtotal_comida", $this->subtotal_comida);
        $stmt->bindParam(":tarifa_envio_cobrada", $this->tarifa_envio_cobrada);
        $stmt->bindParam(":porcentaje_aplicado", $this->porcentaje_aplicado);
        $stmt->bindParam(":comision_restaurante", $this->comision_restaurante);
        $stmt->bindParam(":pago_neto_restaurante", $this->pago_neto_restaurante);
        $stmt->bindParam(":pago_neto_repartidor", $this->pago_neto_repartidor);
        $stmt->bindParam(":ganancia_neta_zippy", $this->ganancia_neta_zippy);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 2. Obtener las finanzas de un pedido específico
     */
    public function obtenerPorPedido($id_pedido) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id_pedido = :id_pedido 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        
        $id_pedido = htmlspecialchars(strip_tags($id_pedido));
        $stmt->bindParam(":id_pedido", $id_pedido);
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id_finanza = $row['id_finanza'];
            $this->subtotal_comida = $row['subtotal_comida'];
            $this->tarifa_envio_cobrada = $row['tarifa_envio_cobrada'];
            $this->porcentaje_aplicado = $row['porcentaje_aplicado'];
            $this->comision_restaurante = $row['comision_restaurante'];
            $this->pago_neto_restaurante = $row['pago_neto_restaurante'];
            $this->pago_neto_repartidor = $row['pago_neto_repartidor'];
            $this->ganancia_neta_zippy = $row['ganancia_neta_zippy'];
            $this->id_liquidacion_restaurante = $row['id_liquidacion_restaurante'];
            $this->id_liquidacion_repartidor = $row['id_liquidacion_repartidor'];
            $this->fecha_calculo = $row['fecha_calculo'];
            return true;
        }
        return false;
    }

    /**
     * 3. Vincular este pedido a una liquidación de Restaurante
     * Se usa cuando le transfieres su dinero al restaurante al final de la semana
     */
    public function liquidarRestaurante() {
        $query = "UPDATE " . $this->table_name . " 
                  SET id_liquidacion_restaurante = :id_liquidacion_restaurante 
                  WHERE id_pedido = :id_pedido";

        $stmt = $this->conn->prepare($query);

        $this->id_liquidacion_restaurante = htmlspecialchars(strip_tags($this->id_liquidacion_restaurante));
        $this->id_pedido = htmlspecialchars(strip_tags($this->id_pedido));

        $stmt->bindParam(":id_liquidacion_restaurante", $this->id_liquidacion_restaurante);
        $stmt->bindParam(":id_pedido", $this->id_pedido);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * 4. Vincular este pedido a una liquidación de Repartidor
     * Se usa cuando le pagas sus ganancias al repartidor
     */
    public function liquidarRepartidor() {
        $query = "UPDATE " . $this->table_name . " 
                  SET id_liquidacion_repartidor = :id_liquidacion_repartidor 
                  WHERE id_pedido = :id_pedido";

        $stmt = $this->conn->prepare($query);

        $this->id_liquidacion_repartidor = htmlspecialchars(strip_tags($this->id_liquidacion_repartidor));
        $this->id_pedido = htmlspecialchars(strip_tags($this->id_pedido));

        $stmt->bindParam(":id_liquidacion_repartidor", $this->id_liquidacion_repartidor);
        $stmt->bindParam(":id_pedido", $this->id_pedido);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>