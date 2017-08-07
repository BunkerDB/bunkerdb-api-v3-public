<?php

/**
 * BunkerDB - PHP Version 0.4
 * Date: 2016-08-19
 *
 */
class BunkerDB
{
    public $platform_id;    // Enum(PLATFORM)
    public $external_id;    // Identificador externo a Bunker
    public $obtained_time;  // Y-m-dTH:i:s+00:00 Fecha/Hora de cuando se obtuvieron los datos
    public $device;         // Enum(DEVICE)
    public $first_name;     // str(100) Nombre del usuario
    public $last_name;      // str(100) Apellido del usuario
    public $gender;         // Enum(f,m) Género del usuario
    public $birthday;       // Date(Y-m-d) Fecha de nacimiento
    public $email;          // str(255) Email del usuario
    public $cellphone;      // str(4-50) Número de teléfono móvil.
    public $phone;          // str(4-50) Número de teléfono
    public $document_type;  // Enum(DOCUMENT_TYPE)
    public $document;       // str(5-20) Documento
    public $document_country;   // Pais emisor del documento (ISO3166) Por defecto: UY
    public $address_country;    // Pais de residencia (ISO3166)
    public $address_state;  // str(255) Estado / Departamento de residencia
    public $address_city;   // str(255) Ciudad de residencia
    public $address;        // str Dirección de residencia
    public $address_line_one; // str Solo valida si no se especifica "address"
    public $address_line_two; // str Solo valida si no se especifica "address"
    public $facebook_uid;   // str(50) Identificador de facebook
    public $twitter_uid;    // str(50) Identificador de Twitter
    public $linkedin_uid;   // str(50) Identificador de LinkedIn
    public $google_uid;     // str(50) Identificador de Google
    public $allow_newsletters;  // Boolean Optin para newsletters
    public $allow_sms;          // Boolean Optin para envio de sms
    public $allow_brand;        // Boolean Optin para recibir informacion de la marca
    public $allow_global;       // Boolean Optin para recibir informacion de otras marcas
    public $address_postal_code; // str(50) Codigo postal
    public $preferences;        // preferencias asignadas

    const DEVICE_DESKTOP         = 'desktop';
    const DEVICE_TABLET          = 'tablet';
    const DEVICE_PHONE           = 'phone';
    const DOCUMENT_TYPE_CITIZEN  = 'CITIZEN';   // Credencial
    const DOCUMENT_TYPE_DNI      = 'DNI';       // DNI - Valor por defecto
    const DOCUMENT_TYPE_DFI      = 'DFI';       // Documento de identificación Federal
    const DOCUMENT_TYPE_DRIVER   = 'DRIVER';    // Licencia de conducción
    const DOCUMENT_TYPE_MILITAR  = 'MILITAR';   // Identificación militar
    const DOCUMENT_TYPE_PASSPORT = 'PASSPORT';  // Pasaporte
    const DOCUMENT_TYPE_SOCIAL   = 'SOCIAL';    // Identificación de seguridad social
    const DOCUMENT_TYPE_CPF      = 'CPF';       // Cadastro de Pessoas Físicas
    const PLATFORM_FACEBOOK_APP  = 'facebook_app';
    const PLATFORM_WEB           = 'web';
    const PLATFORM_GOOGLE        = 'google';

    private $api_endpoint = null;
    private $api_token    = null;

    public function __construct($api_endpoint, $api_token)
    {
        if (!is_null($api_endpoint) && !$api_endpoint == '') {
            $this->api_endpoint = $api_endpoint;
        }
        if (!is_null($api_token) && !$api_token == '') {
            $this->api_token = $api_token;
        }
        if (is_null($this->api_endpoint) || is_null($this->api_token)) {
            Throw new Exception('Se deben especificar el endpoint y token');
        }
    }

    private function getDataJSON()
    {
        $arr    = array();
        $fields = get_object_vars($this);
        foreach ($fields as $field => $value) {
            if ($value) $arr[$field] = $value;
        }
        unset($arr['api_endpoint']);
        unset($arr['api_token']);
        unset($arr['http_code']);
        unset($arr['error_msg']);
        unset($arr['response']);
        return json_encode($arr);
    }

    private function checkData()
    {
        if (!$this->platform_id) {
            Throw new Exception('El atributo platform_id es obligatorio');
        }
    }

    public function submit()
    {
        $this->checkData();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api_endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getDataJSON());
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/hal+json",
            "Authorization: Bearer ".$this->api_token
        ));

        try {
            $this->response  = curl_exec($ch);
            $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $header_size     = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header          = substr($this->response, 0, $header_size);
            $body            = substr($this->response, $header_size, strlen($this->response));

            if ($this->http_code == '201') {
                // Caso único de éxito                
                $this->response = $body;
                $result         = true;
            } else {
                $this->error_msg = ($header) ? $header : curl_error($ch);
                $result          = false;
            }
            curl_close($ch);
        } catch (Exception $e) {
            $this->error_msg = $e->getMessage();
            $result          = false;
        }

        return $result;
    }

    public function getHttpCode()
    {
        return $this->http_code;
    }

    public function getErrorMessage()
    {
        return $this->error_msg;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getInsertedId()
    {
        if ($this->getHttpCode() == '201') {
            $data = json_decode($this->getResponse());
            return $data->id;
        }
        return null;
    }
}
