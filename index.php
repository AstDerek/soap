<?php

define('WSDL', 'http://www.example.com/?wsdl');
define('BASE_PATH', ''); // Sin diagonal final

require_once __DIR__ . '/AltoRouter.php';

/**
 * Manejo de la solicitud SOAP
 *
 * @param int    $juego  Código de juego
 * @param int    $origen
 * @param string $inicio Fecha inicial
 * @param string $final  Fecha final
 * @return array $response
 */
function endPointHandler ($juego, $origen, $inicio, $final) {
    /**
     * @ToDo: almacenar solicitudes en la base de datos, actualizar solo si es necesario
     */
    /**
     * Deshabilitar caché en solicitudes SOAP
     */
    ini_set('soap.wsdl_cache_enabled', 0);
    ini_set('soap.wsdl_cache_ttl', 0);

    /**
     * Habilitar excepciones en caso de errores
     */
    $wsdl = WSDL;
    $client = new SoapClient($wsdl, ['exception' => 1]);

    /**
     * Estructura general de la respuesta
     */
    $response = [
        'status' => 200,
        'message' => '',
        'data' => null,
    ];

    /**
     * Intentar la llamada al método
     */
    try {
        $response['data'] = $client->ws_lista_res($juego, $inicio, $final, $origen);
    } catch (SoapException $e) {
        $response = [
            'status' => 400,
            'message' => $e->getMessage(),
            'data' => null,
        ];
    } catch (Exception $e) {
        $response = [
            'status' => 503,
            'message' => $e->getMessage(),
            'data' => null,
        ];
    }

    return $response;
}

/**
 * Respuesta generica para cuando no se pasan todos los argumentos requeridos
 *
 * @return array
 */
function badRequestHandler () {
    return ['status' => 400];
}

/**
 * Generar respuesta con JSON
 *
 * @param array $response status, message, data
 */
function outputHandler (Array $response=[]) {
    $response = [
        'status' => isset($response['status']) ? $response['status'] : 404,
        'message' => isset($response['message']) ? $response['message'] : null,
        'data' => isset($response['data']) ? $response['data'] : null,
    ];

    /**
     * Ciertos caracteres no se codifican correctamente en JSON, si no estan en UTF-8
     */
    array_walk_recursive($response, function (&$item) {
        if (is_string($item)) {
            $item = utf8_encode($item);
        }
    });

    http_response_code($response['status']);
    header('Content-Type: application/json');

    echo json_encode($response);
}

try {
    /**
     * https://github.com/dannyvankooten/AltoRouter
     */
    $router = new AltoRouter();

    if (BASE_PATH) {
        $router->setBasePath(BASE_PATH);
    }

    $router->addMatchTypes(['date' => '\d{4}-\d{2}-\d{2}']);

    /**
     * Las fechas podrian ser optativas, en cuyo case irian en la cadena de consulta
     * Ya que no lo sabemos, las ponemos en la URI
     */
    $router->map('GET', '/ws-lista-res/[i:juego]/[i:origen]/[date:inicio]/[date:final]', endPointHandler);

    $match = $router->match();

    if ($match && is_callable($match['target'])) {
        $response = call_user_func_array($match['target'], $match['params']);
    } else {
        $response = ['status' => 404];
    }
} catch (Exception $e) {
    $response = ['status' => 503];
}

outputHandler($response);
