<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Orígenes permitidos para embeber
    |--------------------------------------------------------------------------
    |
    | Orígenes que pueden mostrar las rutas embebibles dentro de un iframe,
    | separados por espacio. Se emiten en la directiva CSP frame-ancestors.
    |
    */

    'frame_ancestors' => env(
        'EMBED_FRAME_ANCESTORS',
        'https://meditarenargentina.org https://www.meditarenargentina.org'
    ),

    /*
    |--------------------------------------------------------------------------
    | Rutas embebibles
    |--------------------------------------------------------------------------
    |
    | Nombres de rutas que pueden mostrarse dentro de un iframe. Para estas
    | rutas SecurityHeaders omite X-Frame-Options y envía frame-ancestors.
    |
    */

    'routes' => [
        'grillaembebida',
    ],

];
