<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$ruta = App\Models\ConfiguracionSistema::obtenerTexto('mail_info_membresias_imagen_ruta', '');
$id = App\Models\ConfiguracionSistema::obtenerTexto('mail_info_membresias_imagen_id', '');
$rel = ltrim(str_replace('/storage/', '', $ruta), '/');
$exists = $rel !== '' ? Illuminate\Support\Facades\Storage::disk('public')->exists($rel) : false;
echo "RUTA={$ruta}\n";
echo "ID={$id}\n";
echo "REL={$rel}\n";
echo "EXISTS=" . ($exists ? '1' : '0') . "\n";
