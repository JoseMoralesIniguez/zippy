<?php
class NotificacionService {
    // Reemplaza con tu Server Key de Firebase Console
    private $server_key = 'TU_SERVER_KEY_AQUÍ';

    public function enviarNotificacion($token_destino, $titulo, $cuerpo, $datos = []) {
        $url = 'https://fcm.googleapis.com/fcm/send';

        $campos = [
            'to' => $token_destino,
            'notification' => [
                'title' => $titulo,
                'body' => $cuerpo,
                'sound' => 'default'
            ],
            'data' => $datos // Información extra para que la App reaccione
        ];

        $headers = [
            'Authorization: key=' . $this->server_key,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($campos));

        $resultado = curl_exec($ch);
        curl_close($ch);

        return $resultado;
    }
}