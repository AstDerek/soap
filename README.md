# Prueba de SOAP

El pequeño proyecto hace una llamada a un servicio SOAP, y responde como una API+JSON. Se asume que no hay autenticacion en el servicio.

## Configuracion

* `define('WSDL', '<url>');` URL del WSDL
* `define('BASE_PATH', '<URI sin diagonal final>');` URI base, en caso que el proyecto sea servido desde un subdirectorio

