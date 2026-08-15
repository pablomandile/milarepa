<?php

/*
|--------------------------------------------------------------------------
| Front controller del docroot de producción (Hostinger)
|--------------------------------------------------------------------------
|
| Copia de public/index.php con las rutas apuntando a ../milarepa/, porque en
| el server el docroot (~/domains/milarepa.com.ar/public_html) está afuera del
| proyecto Laravel (~/domains/milarepa.com.ar/milarepa).
|
| Vive versionado acá porque el archivo del server no lo toca ningún deploy y
| ya divergió una vez: tenía ini_set('display_errors', 1) + error_reporting(E_ALL)
| hardcodeados, que pisaban el .user.ini del hosting e imprimían las deprecations
| de PHP 8.4 de vendor/ arriba de la página (rompiendo headers en cold start).
|
| Se copia a mano: scp deploy/public_html/index.php <alias>:~/domains/milarepa.com.ar/public_html/index.php
|
*/

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

// Producción: nunca mostrar errores crudos al visitante. Esta es la única
// ventana en la que manda el display_errors del hosting — apenas Laravel
// arranca, HandleExceptions::bootstrap() lo apaga por su cuenta. Los errores
// se siguen registrando en el error_log del hosting (log_errors no se toca).
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../milarepa/storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../milarepa/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../milarepa/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
