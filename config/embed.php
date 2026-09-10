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
    | Sin valor, sólo el propio sitio puede embeber ('self' se agrega siempre).
    | Cada centro declara sus orígenes en su .env: acá no va el de ninguno.
    |
    */

    'frame_ancestors' => env('EMBED_FRAME_ANCESTORS', ''),

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
