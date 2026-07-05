<?php

/*
|--------------------------------------------------------------------------
| Importación "multievento"
|--------------------------------------------------------------------------
|
| Configuración de la importación de inscripciones desde la planilla maestra
| consolidada (Google Sheets). La planilla debe estar accesible por link
| ("cualquiera con el enlace: lector") o publicada, para poder bajar el CSV
| vía .../export?format=csv.
|
*/

return [

    // URL de edición de la planilla (la que abre el botón "Abrir URL").
    'sheet_url' => env(
        'MULTIEVENTO_SHEET_URL',
        'https://docs.google.com/spreadsheets/d/15y4E50wO835jnlvLCMySwgTcEPeU5OPqbMeLJUpyg5A/edit?usp=sharing'
    ),

    // gid de la pestaña a exportar (0 = primera pestaña).
    'sheet_gid' => env('MULTIEVENTO_SHEET_GID', '0'),

    // Solo se importan filas con FechaEvento >= esta fecha (Y-m-d). Las anteriores se descartan.
    'fecha_corte' => env('MULTIEVENTO_FECHA_CORTE', '2026-01-01'),

];
