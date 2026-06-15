<script>
export default {
    name: 'EstadoInscripcionesImportar',
};
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dropdown from 'primevue/dropdown';
import Dialog from 'primevue/dialog';

const props = defineProps({
    actividades: { type: Array, default: () => [] },
    actividadSeleccionada: { type: Number, default: null },
    preview: { type: Object, default: null },
    resumen: { type: Object, default: null },
    reportes: { type: Array, default: () => [] },
    esAdmin: { type: Boolean, default: false },
});

const columnasDesconocidas = computed(
    () => props.preview?.columnas_desconocidas || props.resumen?.columnas_desconocidas || []
);

function fechaLegible(iso) {
    if (!iso) return '—';
    return String(iso).replace('T', ' ').slice(0, 19);
}

function eliminarReporte(archivo) {
    if (!confirm('¿Eliminar este reporte de importación? Esta acción no se puede deshacer.')) return;
    router.delete(route('estadoinscripciones.importar.reporte.eliminar', { archivo }), {
        preserveScroll: true,
    });
}

const archivo = ref(null);
const archivoNombre = ref('');
const procesando = ref(false);
const errorGeneral = ref('');
const actividadId = ref(props.actividadSeleccionada || null);
const formatoDialogVisible = ref(false);

const actividadOptions = computed(() =>
    props.actividades.map((a) => ({
        id: a.id,
        label: `${a.nombre} — ${(a.fecha_inicio || '').slice(0, 10)}${a.entidad?.abreviacion ? ' (' + a.entidad.abreviacion + ')' : ''}`,
    }))
);

function seleccionarArchivo(event) {
    const f = event.target.files?.[0];
    if (!f) return;
    archivo.value = f;
    archivoNombre.value = f.name;
    errorGeneral.value = '';
}

function enviar(routeName) {
    if (!actividadId.value) {
        errorGeneral.value = 'Seleccioná una Actividad primero';
        return;
    }
    if (!archivo.value) {
        errorGeneral.value = 'Seleccioná un archivo CSV primero';
        return;
    }
    procesando.value = true;
    errorGeneral.value = '';
    router.post(
        route(routeName),
        { archivo: archivo.value, actividad_id: actividadId.value },
        {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors) => {
                errorGeneral.value = errors.archivo || errors.actividad_id || 'Error al procesar el archivo';
            },
            onFinish: () => {
                procesando.value = false;
            },
        }
    );
}

const previsualizar = () => enviar('estadoinscripciones.importar.preview');
const confirmarImportacion = () => enviar('estadoinscripciones.importar.confirmar');

function claseAccion(accion) {
    if (accion === 'error') return 'text-red-600 font-semibold';
    if (accion === 'omitir') return 'text-amber-600 font-semibold';
    return 'text-emerald-600 font-semibold';
}

function etiquetaAccion(fila) {
    if (fila.accion === 'error') return 'Error';
    if (fila.accion === 'omitir') return 'Omitir';
    return fila.user_existe ? 'Crear (usuario existe)' : 'Crear (usuario nuevo)';
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
                Importar Inscripciones desde CSV
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

                <!-- Aviso: columnas desconocidas (se omiten) -->
                <div
                    v-if="columnasDesconocidas.length"
                    class="rounded-md bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 px-4 py-3 text-sm text-amber-800 dark:text-amber-200"
                >
                    <p class="font-semibold">Columnas desconocidas (se omiten):</p>
                    <p class="mt-1">{{ columnasDesconocidas.join(', ') }}</p>
                </div>

                <!-- Card: Actividad -->
                <div class="bg-white dark:bg-gray-800 shadow-soft-indigo sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">1. Seleccionar Actividad</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Las inscripciones del CSV se importan a esta actividad. Se pueden re-subir el archivo completo
                        las veces que quieras: los emails ya inscriptos (a esta actividad) se omiten automáticamente.
                    </p>
                    <Dropdown
                        v-model="actividadId"
                        :options="actividadOptions"
                        optionLabel="label"
                        optionValue="id"
                        filter
                        placeholder="Seleccioná una Actividad"
                        class="w-full md:w-[40rem] border border-gray-300 dark:border-gray-600"
                    />
                </div>

                <!-- Card: Upload -->
                <div class="bg-white dark:bg-gray-800 shadow-soft-indigo sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">2. Seleccionar archivo CSV</h2>
                    <div class="flex items-center gap-3 flex-wrap">
                        <label class="cursor-pointer bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded inline-flex items-center gap-2">
                            <i class="pi pi-upload"></i>
                            Seleccionar CSV
                            <input type="file" accept=".csv,text/csv" class="hidden" @change="seleccionarArchivo" />
                        </label>
                        <button
                            type="button"
                            class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded inline-flex items-center gap-2"
                            @click="formatoDialogVisible = true"
                        >
                            <i class="pi pi-info-circle"></i>
                            Formato del archivo
                        </button>
                        <span v-if="archivoNombre" class="text-sm text-gray-700 dark:text-gray-300">{{ archivoNombre }}</span>
                        <button
                            type="button"
                            class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-medium py-2 px-4 rounded"
                            :disabled="!archivo || procesando"
                            @click="previsualizar"
                        >
                            {{ procesando ? 'Procesando...' : 'Previsualizar' }}
                        </button>
                    </div>
                    <p v-if="errorGeneral" class="mt-3 text-sm text-red-600">{{ errorGeneral }}</p>
                </div>

                <!-- Card: Preview -->
                <div v-if="preview" class="bg-white dark:bg-gray-800 shadow-soft-indigo sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Vista previa</h2>

                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
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
                            <p class="text-xs text-amber-700 dark:text-amber-300">Omitidas (ya inscriptas / dup.)</p>
                            <p class="text-2xl font-semibold text-amber-700 dark:text-amber-300">{{ preview.omitidas }}</p>
                        </div>
                        <div class="border border-red-200 dark:border-red-800 rounded p-3 bg-red-50 dark:bg-red-900/20">
                            <p class="text-xs text-red-700 dark:text-red-300">Errores</p>
                            <p class="text-2xl font-semibold text-red-700 dark:text-red-300">{{ preview.errores }}</p>
                        </div>
                    </div>

                    <DataTable :value="preview.filas" stripedRows paginator :rows="20" :rowsPerPageOptions="[10, 20, 50, 100]" tableStyle="min-width: 60rem" class="text-sm">
                        <Column field="linea" header="Línea" style="width: 5rem"></Column>
                        <Column header="Acción" style="width: 11rem">
                            <template #body="{ data }">
                                <span :class="claseAccion(data.accion)">{{ etiquetaAccion(data) }}</span>
                            </template>
                        </Column>
                        <Column header="Email">
                            <template #body="{ data }">{{ data.datos?.email || '—' }}</template>
                        </Column>
                        <Column header="Nombre">
                            <template #body="{ data }">{{ data.datos?.name || '—' }}</template>
                        </Column>
                        <Column header="Membresía">
                            <template #body="{ data }">{{ data.datos?.membresia_nombre || '—' }}</template>
                        </Column>
                        <Column header="Modalidad" style="width: 7rem">
                            <template #body="{ data }">
                                <span
                                    v-if="data.datos?.modalidad"
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                    :class="data.datos.modalidad === 'Online' ? 'bg-sky-100 text-sky-800' : 'bg-gray-100 text-gray-700'"
                                >
                                    {{ data.datos.modalidad }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </template>
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
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <div class="border border-emerald-200 dark:border-emerald-800 rounded p-3 bg-emerald-50 dark:bg-emerald-900/20">
                            <p class="text-xs text-emerald-700 dark:text-emerald-300">Inscripciones creadas</p>
                            <p class="text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ resumen.creadas }}</p>
                        </div>
                        <div class="border border-amber-200 dark:border-amber-800 rounded p-3 bg-amber-50 dark:bg-amber-900/20">
                            <p class="text-xs text-amber-700 dark:text-amber-300">Omitidas</p>
                            <p class="text-2xl font-semibold text-amber-700 dark:text-amber-300">{{ resumen.omitidas }}</p>
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
                        <Column header="Actividad">
                            <template #body="{ data }">{{ data.actividad_nombre || '—' }}</template>
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
                        <Column header="Errores" style="width: 6rem">
                            <template #body="{ data }">{{ data.errores }}</template>
                        </Column>
                        <Column header="Col. desconocidas">
                            <template #body="{ data }">
                                <span v-if="data.columnas_desconocidas?.length" class="text-amber-700 dark:text-amber-400">
                                    {{ data.columnas_desconocidas.join(', ') }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </template>
                        </Column>
                        <Column header="Acciones" style="width: 11rem">
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <a
                                        :href="route('estadoinscripciones.importar.reporte.descargar', { archivo: data.archivo })"
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

        <Dialog v-model:visible="formatoDialogVisible" header="Formato del archivo CSV" :style="{ width: '52rem' }" :breakpoints="{ '960px': '90vw' }" dismissableMask modal>
            <div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
                <p>CSV con la primera fila de headers (export de Google Forms del evento). Detecta encoding y separador automáticamente.</p>
                <ul class="list-disc list-inside space-y-1">
                    <li><strong>Dirección de correo electrónico</strong> → clave para matchear/crear el usuario (obligatorio y válido).</li>
                    <li><strong>Nombre + Apellido</strong> → nombre del usuario (si faltan, se usa el email).</li>
                    <li><strong>Membresía</strong> ("TK CORAZÓN CMKA", "SIN MEMBRESIA", …) → se mapea a la membresía + entidad (CMKA / Nagaryhuna).</li>
                    <li><strong>Pendiente</strong> = monto esperado; <strong>Valor / FechaPago / Forma</strong> = pago realizado (estado Saldado, fecha y referencia).</li>
                    <li><strong>¿Bajo qué modalidad vas a participar?</strong> ("ONLINE" / "PRESENCIAL") define si la inscripción es online. Para <strong>ONLINE</strong> se verifica que el usuario tenga <strong>membresía online</strong> (según el email de <strong>Dirección de correo electrónico TK (ONLINE)</strong>, o el email principal si está vacío); si no la tiene, igual se inscribe y se deja constancia en las <strong>observaciones</strong>.</li>
                    <li>Si el email <strong>ya tiene inscripción</strong> a la actividad o se <strong>repite en el CSV</strong> → se omite y se reporta.</li>
                    <li>Para emails que no existen como usuario, se crea un usuario nuevo (rol asistant, contraseña <code>{Apellido}2026</code>).</li>
                    <li>Toda la importación corre en una transacción (rollback si algo falla).</li>
                </ul>
            </div>
        </Dialog>
    </AppLayout>
</template>
