# deploy/

Archivos que viven **en el server de producción pero fuera del proyecto Laravel**, versionados acá
para que no se pierdan ni diverjan (ningún paso del deploy los toca automáticamente).

## Layout en Hostinger

```
~/domains/milarepa.com.ar/
├── milarepa/          <- proyecto Laravel (esto es lo que sube el deploy)
│   └── public/        <- NO es el docroot; se le copia el build igual, por las dudas
└── public_html/       <- DOCROOT REAL de milarepa.com.ar
    ├── index.php      <- deploy/public_html/index.php
    ├── build/         <- copia de public/build
    └── storage        <- symlink a ../milarepa/storage/app/public
```

## `public_html/index.php`

Es `public/index.php` con las rutas apuntando a `../milarepa/`. Se copia a mano:

```bash
scp deploy/public_html/index.php agendaflex:~/domains/milarepa.com.ar/public_html/index.php
```

**Historia**: el archivo del server tenía `ini_set('display_errors', 1)` y `error_reporting(E_ALL)`
hardcodeados, que pisaban el `.user.ini` del hosting y hacían que las deprecations de PHP 8.4 de
`vendor/` se imprimieran arriba de la página (con riesgo de romper headers, redirects y respuestas
JSON en cold start). Si volvés a ver deprecations en producción, revisá primero este archivo.
