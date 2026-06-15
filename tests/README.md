# Tests

## Cómo correr la suite de regresión

```bash
composer test:regression   # = php artisan test
```

Toda la suite usa `DatabaseTransactions` (ningún test usa `RefreshDatabase`), así que correr
`php artisan test` completo es **seguro** (no toca/borra la BD) y está **verde** (30 tests).

Al terminar, genera un reporte JSON consolidado en la raíz del proyecto:
**`test-report.json`** (está en `.gitignore`). Estructura:

```json
{
  "generated_at": "2026-06-13T21:00:00-03:00",
  "summary": { "total": 30, "passed": 30, "failed": 0, "errored": 0, "skipped": 0, "incomplete": 0, "success": true },
  "failures": [ { "test": "Clase::metodo", "status": "failed|errored", "message": "..." } ],
  "tests":    [ { "test": "Clase::metodo", "status": "passed|failed|...", "message": null } ]
}
```

El reporte lo escribe la extensión `Tests\Support\JsonReportExtension` (registrada en `phpunit.xml`),
así que se genera con **cualquier** corrida de PHPUnit/`artisan test`.

## Base de datos de test

- Conexión MySQL `milarepa_testing` (ver `phpunit.xml` y `.env.testing`).
- Los tests usan **`DatabaseTransactions`**: asumen una BD ya migrada y con datos base
  (roles, permisos, países, etc.), y envuelven cada test en una transacción que se revierte.
- **Refrescar la BD de test con datos reales** (dump de `milarepa` → `milarepa_testing`, ambas locales):
  ```bash
  mysqldump -h127.0.0.1 -uroot --single-transaction --routines --triggers --no-tablespaces milarepa > storage/app/milarepa_dump.sql
  mysql -h127.0.0.1 -uroot -e "DROP DATABASE IF EXISTS milarepa_testing; CREATE DATABASE milarepa_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  mysql -h127.0.0.1 -uroot milarepa_testing < storage/app/milarepa_dump.sql
  php artisan migrate --env=testing --force   # aplica migraciones pendientes (debería ser "Nothing to migrate")
  rm storage/app/milarepa_dump.sql            # el dump tiene datos reales (PII): borrarlo
  ```
- Si solo se perdieron roles/permisos, alcanza con:
  ```bash
  php artisan db:seed --class=Database\\Seeders\\RoleSeeder --env=testing --force
  ```

## Qué cubre la suite

- **`tests/Feature/ImageUpload/`** — subida de imagen diferida al guardar (trait
  `ProcesaImagenAlGuardar`): crea la `Imagen` solo si la persistencia tiene éxito, revierte y borra
  el archivo si falla, conserva/reemplaza la imagen al editar. Cubre el trait (unit) + el flujo HTTP
  en Maestro, Libro y MetodoPago.
- **`tests/Feature/Smoke/`** — páginas index clave responden 2xx para un usuario autenticado.
- **`tests/Feature/Security/`** — tests de seguridad existentes (permisos, IDOR, throttling, headers).
- **`tests/Feature/RenovarMembresiasMensualTest.php`** — comando `membresias:renovar-mensual`:
  expira el mes anterior y genera el nuevo (en blanco/impago para no-suscripción; pagado para suscripción).
