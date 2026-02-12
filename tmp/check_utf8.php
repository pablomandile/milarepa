<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$bad = [];
foreach (App\Models\Membresia::with(['entidad','botonPago'])->get() as $m) {
    foreach (['nombre','descripcion'] as $f) {
        $v = $m->$f;
        if ($v !== null && !mb_check_encoding($v, 'UTF-8')) {
            $bad[] = ['membresia', $m->id, $f];
        }
    }
    if ($m->entidad) {
        foreach (['nombre'] as $f) {
            $v = $m->entidad->$f;
            if ($v !== null && !mb_check_encoding($v, 'UTF-8')) {
                $bad[] = ['entidad', $m->entidad->id, $f];
            }
        }
    }
    if ($m->botonPago) {
        foreach (['nombre','descripcion','link'] as $f) {
            $v = $m->botonPago->$f;
            if ($v !== null && !mb_check_encoding($v, 'UTF-8')) {
                $bad[] = ['botonPago', $m->botonPago->id, $f];
            }
        }
    }
}
var_export($bad);
