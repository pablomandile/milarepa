<script>
export default {
    name: 'EstadoInscripcionesImportarMultievento',
};
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dropdown from 'primevue/dropdown';

const props = defineProps({
    actividades: { type: Array, default: () => [] },
    preview: { type: Object, default: null },
    resumen: { type: Object, default: null },
    sheetUrl: { type: String, default: '' },
    reportes: { type: Array, default: () => [] },
    esAdmin: { type: Boolean, default: false },
});

const archivo = ref(null);
const archivoNombre = ref('');
const fuente = ref('url'); // 'url' | 'archivo'
const procesando = ref(false);
const errorGeneral = ref('');

// Mapeo evento(clave) -> actividad_id, inicializado desde la vista previa.
const asignaciones = ref({});

watch(
    () => props.preview,
    (p) => {
        if (!p?.eventos) return;
        const nuevo = {};
        for (const ev of p.eventos) nuevo[ev.clave] = ev.asignada_id ?? null;
        asignaciones.value = nuevo;
    },
    { immediate: true }
);

const columnasDesconocidas = computed(
    () => props.preview?.columnas_desconocidas || props.resumen?.columnas_desconocidas || []
);

const actividadOptions = computed(() =>
    props.actividades.map((a) => ({
        id: a.id,
        label: `${a.nombre} — ${(a.fecha_inicio || '').slice(0, 10)}${a.entidad?.abreviacion ? ' (' + a.entidad.abreviacion + ')' : ''}`,
    }))
);

function fechaLegible(iso) {
    if (!iso) return '—';
    return String(iso).replace('T', ' ').slice(0, 19);
}

function abrirUrl() {
    if (props.sheetUrl) window.open(props.sheetUrl, '_blank', 'noopener');
}

function seleccionarArchivo(event) {
    const f = event.target.files?.[0];
    if (!f) return;
    archivo.value = f;
    archivoNombre.value = f.name;
    fuente.value = 'archivo';
    errorGeneral.value = '';
}

function enviar(routeName) {
    if (fuente.value === 'archivo' && !archivo.value) {
        errorGeneral.value = 'Seleccioná un archivo CSV primero';
        return;
    }
    const data = { mapeo: JSON.stringify(asignaciones.value || {}) };
    if (fuente.value === 'url') data.desde_url = true;
    else data.archivo = archivo.value;

    procesando.value = true;
    errorGeneral.value = '';
    router.post(route(routeName), data, {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true, // conserva el archivo elegido y el mapeo entre preview y confirmar
        onError: (errors) => {
            errorGeneral.value = errors.archivo || errors.desde_url || errors.mapeo || 'Error al procesar la importación';
        },
        onFinish: () => {
            procesando.value = false;
        },
    });
}

function previsualizarDesdeUrl() {
    fuente.value = 'url';
    enviar('estadoinscripciones.importar-multievento.preview');
}
const previsualizarArchivo = () => enviar('estadoinscripciones.importar-multievento.preview');
const actualizarPrevia = () => enviar('estadoinscripciones.importar-multievento.preview');
const confirmarImportacion = () => enviar('estadoinscripciones.importar-multievento.confirmar');

function eliminarReporte(archivoNom) {
    if (!confirm('¿Eliminar este reporte de importación? Esta acción no se puede deshacer.')) return;
    router.delete(route('estadoinscripciones.importar-multievento.reporte.eliminar', { archivo: archivoNom }), {
        preserveScroll: true,
    });
}

function claseAccion(accion) {
    if (accion === 'error') return 'text-red-600 font-semibold';
    if (accion === 'omitir') return 'text-amber-600 font-semibold';
    if (accion === 'sin_actividad') return 'text-purple-600 font-semibold';
    if (accion === 'descartada_fecha') return 'text-gray-500 font-semibold';
    return 'text-emerald-600 font-semibold';
}

function etiquetaAccion(fila) {
    switch (fila.accion) {
        case 'error':
            return 'Error';
        case 'omitir':
            return 'Omitir';
        case 'sin_actividad':
            return 'Sin actividad';
        case 'descartada_fecha':
            return 'Fuera de fecha';
        default:
            return fila.user_existe ? 'Crear (usuario existe)' : 'Crear (usuario nuevo)';
    }
}

function formatoMonto(v) {
    if (v === null || v === undefined || v === '') return '—';
    return '$' + Number(v).toLocaleString('es-AR');
}
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                Importar Inscripciones — Multievento
            </h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="flex justify-end">
                    <Link
                        :href="route('estadoinscripciones.index')"
                        class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"
                    >
                        Volver a Estado de Inscripciones
                    </Link>
                </div>

                <div
                    v-if="resumen?.mensaje"
                    class="rounded-md bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200"
                >
                    {{ resumen.mensaje }}
                </div>

                <div
                    v-if="columnasDesconocidas.length"
                    class="rounded-md bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 px-4 py-3 text-sm text-amber-800 dark:text-amber-200"
                >
                    <p class="font-semibold">Columnas desconocidas (se omiten):</p>
                    <p class="mt-1">{{ columnasDesconocidas.join(', ') }}</p>
                </div>

                <!-- Card: Fuente -->
                <div class="bg-white dark:bg-gray-800 shadow-soft-indigo sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">1. Origen de los datos</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Importá directamente desde la planilla maestra de Google Sheets, o subí el CSV exportado.
                        Cada fila se rutea a su actividad (lo confirmás abajo). Se puede re-importar las veces que
                        quieras: las personas ya inscriptas a cada actividad se omiten automáticamente.
                    </p>
                    <div class="flex items-center gap-3 flex-wrap">
                        <button
                            type="button"
                            class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded inline-flex items-center gap-2"
                            @click="abrirUrl"
                        >
                            <i class="pi pi-external-link"></i>
                            Abrir URL
                        </button>
                        <button
                            type="button"
                            class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-medium py-2 px-4 rounded inline-flex items-center gap-2"
                            :disabled="procesando"
                            @click="previsualizarDesdeUrl"
                        >
                            <i class="pi pi-cloud-download"></i>
                            {{ procesando ? 'Procesando...' : 'Importar desde la planilla' }}
                        </button>

                        <span class="text-gray-400">·</span>

                        <label class="cursor-pointer bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded inline-flex items-center gap-2">
                            <i class="pi pi-upload"></i>
                            Seleccionar CSV
                            <input type="file" accept=".csv,text/csv" class="hidden" @change="seleccionarArchivo" />
                        </label>
                        <span v-if="archivoNombre" class="text-sm text-gray-700 dark:text-gray-300">{{ archivoNombre }}</span>
                        <button
                            v-if="archivo"
                            type="button"
                            class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-medium py-2 px-4 rounded"
                            :disabled="procesando"
                            @click="previsualizarArchivo"
                        >
                            {{ procesando ? 'Procesando...' : 'Previsualizar CSV' }}
                        </button>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400 break-all">Planilla: {{ sheetUrl }}</p>
                    <p v-if="errorGeneral" class="mt-3 text-sm text-red-600">{{ errorGeneral }}</p>
                </div>

                <!-- Card: Preview -->
                <div v-if="preview" class="bg-white dark:bg-gray-800 shadow-soft-indigo sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Vista previa</h2>

                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-4">
                        <div class="border border-gray-200 dark:border-gray-700 rounded p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Filas totales</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ preview.total_filas }}</p>
                        </div>
                        <div class="border border-emerald-200 dark:border-emerald-800 rounded p-3 bg-emerald-50 dark:bg-emerald-900/20">
                            <p class="text-xs text-emerald-700 dark:text-emerald-300">A crear</p>
                            <p class="text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ preview.a_crear }}</p>
                        </div>
                        <div class="border border-blue-200 dark:border-blue-800 rounded p-3 bg-blue-50 dark:bg-blue-900/20">
                            <p class="text-xs text-blue-700 dark:text-blue-300">Usuarios nuevos</p>
                            <p class="text-2xl font-semibold text-blue-700 dark:text-blue-300">{{ preview.usuarios_nuevos }}</p>
                        </div>
                        <div class="border border-amber-200 dark:border-amber-800 rounded p-3 bg-amber-50 dark:bg-amber-900/20">
                            <p class="text-xs text-amber-700 dark:text-amber-300">Omitidas</p>
                            <p class="text-2xl font-semibold text-amber-700 dark:text-amber-300">{{ preview.omitidas }}</p>
                        </div>
                        <div class="border border-purple-200 dark:border-purple-800 rounded p-3 bg-purple-50 dark:bg-purple-900/20">
                            <p class="text-xs text-purple-700 dark:text-purple-300">Sin actividad</p>
                            <p class="text-2xl font-semibold text-purple-700 dark:text-purple-300">{{ preview.sin_actividad }}</p>
                        </div>
                        <div class="border border-gray-200 dark:border-gray-700 rounded p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Fuera de fecha</p>
                            <p class="text-2xl font-semibold text-gray-600 dark:text-gray-300">{{ preview.descartadas_fecha }}</p>
                        </div>
                        <div class="border border-red-200 dark:border-red-800 rounded p-3 bg-red-50 dark:bg-red-900/20">
                            <p class="text-xs text-red-700 dark:text-red-300">Errores</p>
                            <p class="text-2xl font-semibold text-red-700 dark:text-red-300">{{ preview.errores }}</p>
                        </div>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        Fecha de corte: solo se importan eventos con fecha ≥ <strong>{{ preview.fecha_corte }}</strong>.
                    </p>

                    <!-- Mapeo de eventos -->
                    <h3 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-2">Eventos detectados → Actividad</h3>
                    <DataTable :value="preview.eventos" stripedRows class="text-sm mb-3" tableStyle="min-width: 50rem">
                        <Column header="Evento (NombreEvento)">
                            <template #body="{ data }">
                                <div class="font-medium">{{ data.nombre_evento }}</div>
                                <div class="text-xs text-gray-500">{{ data.fecha_evento }} · {{ data.filas }} fila(s)</div>
                            </template>
                        </Column>
                        <Column header="Actividad destino" style="width: 42rem">
                            <template #body="{ data }">
                                <Dropdown
                                    v-model="asignaciones[data.clave]"
                                    :options="actividadOptions"
                                    optionLabel="label"
                                    optionValue="id"
                                    filter
                                    showClear
                                    placeholder="— Sin asignar —"
                                    class="w-full border border-gray-300 dark:border-gray-600"
                                />
                                <div v-if="data.sugerida_nombre && data.origen_sugerencia === 'guardado'" class="text-xs text-teal-600 mt-1">
                                    <i class="pi pi-bookmark"></i> Guardada (match previo): {{ data.sugerida_nombre }}
                                </div>
                                <div v-else-if="data.sugerida_nombre" class="text-xs text-gray-500 mt-1">
                                    Sugerida: {{ data.sugerida_nombre }}
                                </div>
                                <div v-else class="text-xs text-amber-600 mt-1">Sin sugerencia automática</div>
                            </template>
                        </Column>
                    </DataTable>

                    <div class="flex justify-end gap-2 mb-6">
                        <button
                            type="button"
                            class="bg-gray-600 hover:bg-gray-700 disabled:opacity-50 text-white font-medium py-2 px-4 rounded inline-flex items-center gap-2"
                            :disabled="procesando"
                            @click="actualizarPrevia"
                        >
                            <i class="pi pi-refresh"></i>
                            Actualizar vista previa
                        </button>
                    </div>

                    <!-- Detalle de filas -->
                    <DataTable :value="preview.filas" stripedRows paginator :rows="20" :rowsPerPageOptions="[10, 20, 50, 100]" tableStyle="min-width: 60rem" class="text-sm">
                        <Column field="linea" header="Línea" style="width: 5rem"></Column>
                        <Column header="Acción" style="width: 11rem">
                            <template #body="{ data }">
                                <span :class="claseAccion(data.accion)">{{ etiquetaAccion(data) }}</span>
                            </template>
                        </Column>
                        <Column header="Evento">
                            <template #body="{ data }">
                                <div>{{ data.nombre_evento || '—' }}</div>
                                <div class="text-xs text-gray-500">{{ data.fecha_evento || '' }}</div>
                            </template>
                        </Column>
                        <Column header="Email">
                            <template #body="{ data }">{{ data.datos?.email || '—' }}</template>
                        </Column>
                        <Column header="Nombre">
                            <template #body="{ data }">{{ data.datos?.name || '—' }}</template>
                        </Column>
                        <Column header="Monto" style="width: 7rem">
                            <template #body="{ data }">{{ formatoMonto(data.datos?.monto) }}</template>
                        </Column>
                        <Column header="Pago" style="width: 6rem">
                            <template #body="{ data }">{{ data.datos?.pago || '—' }}</template>
                        </Column>
                        <Column header="Avisos">
                            <template #body="{ data }">
                                <ul v-if="data.mensajes && data.mensajes.length" class="list-disc list-inside text-xs text-amber-700 dark:text-amber-400">
                                    <li v-for="(msg, i) in data.mensajes" :key="i">{{ msg }}</li>
                                </ul>
                                <span v-else class="text-xs text-gray-400">—</span>
                            </template>
                        </Column>
                    </DataTable>

                    <div class="mt-4 flex justify-end">
                        <button
                            type="button"
                            class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium py-2 px-6 rounded inline-flex items-center gap-2"
                            :disabled="procesando || preview.a_crear === 0"
                            @click="confirmarImportacion"
                        >
                            <i class="pi pi-check"></i>
                            {{ procesando ? 'Importando...' : `Confirmar importación (${preview.a_crear} inscripciones)` }}
                        </button>
                    </div>
                </div>

                <!-- Card: Resultado -->
                <div v-if="resumen" class="bg-white dark:bg-gray-800 shadow-soft-indigo sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Resultado de la importación</h2>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        <div class="border border-emerald-200 dark:border-emerald-800 rounded p-3 bg-emerald-50 dark:bg-emerald-900/20">
                            <p class="text-xs text-emerald-700 dark:text-emerald-300">Creadas</p>
                            <p class="text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ resumen.creadas }}</p>
                        </div>
                        <div class="border border-amber-200 dark:border-amber-800 rounded p-3 bg-amber-50 dark:bg-amber-900/20">
                            <p class="text-xs text-amber-700 dark:text-amber-300">Omitidas</p>
                            <p class="text-2xl font-semibold text-amber-700 dark:text-amber-300">{{ resumen.omitidas }}</p>
                        </div>
                        <div class="border border-purple-200 dark:border-purple-800 rounded p-3 bg-purple-50 dark:bg-purple-900/20">
                            <p class="text-xs text-purple-700 dark:text-purple-300">Sin actividad</p>
                            <p class="text-2xl font-semibold text-purple-700 dark:text-purple-300">{{ resumen.sin_actividad }}</p>
                        </div>
                        <div class="border border-gray-200 dark:border-gray-700 rounded p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Fuera de fecha</p>
                            <p class="text-2xl font-semibold text-gray-600 dark:text-gray-300">{{ resumen.descartadas_fecha }}</p>
                        </div>
                        <div class="border border-red-200 dark:border-red-800 rounded p-3 bg-red-50 dark:bg-red-900/20">
                            <p class="text-xs text-red-700 dark:text-red-300">Errores</p>
                            <p class="text-2xl font-semibold text-red-700 dark:text-red-300">{{ resumen.errores }}</p>
                        </div>
                    </div>
                </div>

                <!-- Card: Reportes guardados -->
                <div class="bg-white dark:bg-gray-800 shadow-soft-indigo sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Reportes de importación guardados</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Se conserva un reporte JSON por cada importación. Solo un administrador puede eliminarlos.
                    </p>

                    <p v-if="!reportes.length" class="text-sm text-gray-400">Todavía no hay reportes guardados.</p>

                    <DataTable v-else :value="reportes" stripedRows paginator :rows="10" :rowsPerPageOptions="[10, 20, 50]" class="text-sm">
                        <Column header="Fecha">
                            <template #body="{ data }">{{ fechaLegible(data.generado_en) }}</template>
                        </Column>
                        <Column header="Usuario">
                            <template #body="{ data }">{{ data.usuario || '—' }}</template>
                        </Column>
                        <Column header="Creadas" style="width: 6rem">
                            <template #body="{ data }">{{ data.creadas }}</template>
                        </Column>
                        <Column header="Omitidas" style="width: 6rem">
                            <template #body="{ data }">{{ data.omitidas }}</template>
                        </Column>
                        <Column header="Sin act." style="width: 6rem">
                            <template #body="{ data }">{{ data.sin_actividad }}</template>
                        </Column>
                        <Column header="Errores" style="width: 6rem">
                            <template #body="{ data }">{{ data.errores }}</template>
                        </Column>
                        <Column header="Acciones" style="width: 11rem">
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <a
                                        :href="route('estadoinscripciones.importar-multievento.reporte.descargar', { archivo: data.archivo })"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-1 rounded bg-gray-600 hover:bg-gray-700 text-white px-2 py-1 text-xs"
                                    >
                                        <i class="pi pi-download"></i> Ver
                                    </a>
                                    <button
                                        v-if="esAdmin"
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded bg-red-600 hover:bg-red-700 text-white px-2 py-1 text-xs"
                                        @click="eliminarReporte(data.archivo)"
                                    >
                                        <i class="pi pi-trash"></i> Eliminar
                                    </button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
