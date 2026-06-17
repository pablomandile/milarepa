<script>
export default {
    name: 'ConfiguracionImportarMembresias',
};
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dropdown from 'primevue/dropdown';
import Dialog from 'primevue/dialog';

const props = defineProps({
    entidades: {
        type: Array,
        default: () => [],
    },
    entidadSeleccionada: {
        type: Number,
        default: null,
    },
    membresiasConAbreviacion: {
        type: Array,
        default: () => [],
    },
    preview: {
        type: Object,
        default: null,
    },
    resumen: {
        type: Object,
        default: null,
    },
});

const archivo = ref(null);
const archivoNombre = ref('');
const procesando = ref(false);
const errorGeneral = ref('');
const entidadId = ref(props.entidadSeleccionada || null);
const formatoDialogVisible = ref(false);

function seleccionarArchivo(event) {
    const f = event.target.files?.[0];
    if (!f) return;
    archivo.value = f;
    archivoNombre.value = f.name;
    errorGeneral.value = '';
}

function previsualizar() {
    if (!entidadId.value) {
        errorGeneral.value = 'Seleccioná una Entidad antes de previsualizar';
        return;
    }
    if (!archivo.value) {
        errorGeneral.value = 'Seleccioná un archivo CSV primero';
        return;
    }
    procesando.value = true;
    errorGeneral.value = '';
    router.post(
        route('configuracion.importar-membresias.preview'),
        { archivo: archivo.value, entidad_id: entidadId.value },
        {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors) => {
                errorGeneral.value = errors.archivo || errors.entidad_id || 'Error al procesar el archivo';
            },
            onFinish: () => {
                procesando.value = false;
            },
        }
    );
}

function confirmarImportacion() {
    if (!entidadId.value) {
        errorGeneral.value = 'Seleccioná una Entidad antes de confirmar';
        return;
    }
    if (!archivo.value) {
        errorGeneral.value = 'Volvé a seleccionar el archivo antes de confirmar';
        return;
    }
    procesando.value = true;
    router.post(
        route('configuracion.importar-membresias.confirmar'),
        { archivo: archivo.value, entidad_id: entidadId.value },
        {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors) => {
                errorGeneral.value = errors.archivo || errors.entidad_id || 'Error al importar';
            },
            onFinish: () => {
                procesando.value = false;
            },
        }
    );
}

function claseAccion(fila) {
    if (fila.accion === 'error') return 'text-red-600 font-semibold';
    if (fila.user_existe && fila.sin_cambios) return 'text-gray-400';
    return fila.user_existe ? 'text-blue-600' : 'text-emerald-600';
}

function etiquetaAccion(fila) {
    if (fila.accion === 'error') return 'Error';
    if (fila.user_existe && fila.sin_cambios) return 'Sin cambios';
    return fila.user_existe ? 'Actualizar' : 'Crear';
}
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                Importar Membresías desde CSV
            </h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="flex justify-end">
                    <Link
                        :href="route('paginas.configuracion')"
                        class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"
                    >
                        Volver a Configuración
                    </Link>
                </div>

                <!-- Mensaje de resultado -->
                <div
                    v-if="resumen?.mensaje"
                    class="rounded-md bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200"
                >
                    {{ resumen.mensaje }}
                </div>

                <!-- Card: Selección de Entidad -->
                <div class="bg-white dark:bg-gray-800 shadow-soft-indigo sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                        1. Seleccionar Entidad
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Las membresías importadas se van a asociar a esta entidad. El lookup de la columna <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">TK</code> se hace solo entre las membresías de la entidad seleccionada.
                    </p>
                    <Dropdown
                        v-model="entidadId"
                        :options="entidades"
                        optionLabel="nombre"
                        optionValue="id"
                        placeholder="Seleccioná una Entidad"
                        class="w-full md:w-96 border border-gray-300 dark:border-gray-600"
                    />
                </div>

                <!-- Card: Membresías con abreviación -->
                <div v-if="entidadId" class="bg-white dark:bg-gray-800 shadow-soft-indigo sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                        Membresías disponibles para mapeo
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        En el CSV, la columna <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">TK</code> debe coincidir con la abreviación de una de estas membresías para que se asocie al usuario.
                        Las membresías sin abreviación se pueden editar desde
                        <Link :href="route('membresias.gestion')" class="text-indigo-600 hover:underline">Gestión de Membresías</Link>.
                    </p>
                    <div v-if="entidadSeleccionada !== entidadId" class="text-sm text-amber-600 dark:text-amber-400 mb-2">
                        ℹ Presioná "Previsualizar" para refrescar las membresías de esta entidad.
                    </div>
                    <div v-else-if="membresiasConAbreviacion.length === 0" class="text-sm text-amber-600 dark:text-amber-400">
                        ⚠ No hay ninguna membresía con abreviación cargada para esta entidad. Cargá al menos una para poder asociar usuarios.
                    </div>
                    <div v-else class="flex flex-wrap gap-2">
                        <span
                            v-for="m in membresiasConAbreviacion"
                            :key="m.id"
                            class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-sm rounded"
                        >
                            <span class="font-mono font-semibold">{{ m.abreviacion }}</span>
                            <span class="text-gray-600 dark:text-gray-400">→ {{ m.nombre }}</span>
                        </span>
                    </div>
                </div>

                <!-- Card: Upload -->
                <div class="bg-white dark:bg-gray-800 shadow-soft-indigo sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                        2. Seleccionar archivo CSV
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Columnas esperadas: <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">Nombre, Apellido, Activo, TK, Programa, ONLINE, Mail, Teléfonos, Ciudad, Newsletter</code>
                        <span class="block mt-1">Opcionales (pago del mes en curso, al final): <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">Imp., Día, Modo, Nota</code></span>
                    </p>

                    <div class="flex items-center gap-3 flex-wrap">
                        <label
                            class="cursor-pointer bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded inline-flex items-center gap-2"
                        >
                            <i class="pi pi-upload"></i>
                            Seleccionar CSV
                            <input
                                type="file"
                                accept=".csv,text/csv"
                                class="hidden"
                                @change="seleccionarArchivo"
                            />
                        </label>
                        <button
                            type="button"
                            class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded inline-flex items-center gap-2"
                            @click="formatoDialogVisible = true"
                        >
                            <i class="pi pi-info-circle"></i>
                            Formato del archivo CSV
                        </button>
                        <span v-if="archivoNombre" class="text-sm text-gray-700 dark:text-gray-300">
                            {{ archivoNombre }}
                        </span>
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
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Vista previa
                    </h2>

                    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-3 mb-4">
                        <div class="border border-gray-200 dark:border-gray-700 rounded p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Filas totales</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ preview.total_filas }}</p>
                        </div>
                        <div class="border border-emerald-200 dark:border-emerald-800 rounded p-3 bg-emerald-50 dark:bg-emerald-900/20">
                            <p class="text-xs text-emerald-700 dark:text-emerald-300">Usuarios nuevos</p>
                            <p class="text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ preview.usuarios_nuevos }}</p>
                        </div>
                        <div class="border border-blue-200 dark:border-blue-800 rounded p-3 bg-blue-50 dark:bg-blue-900/20">
                            <p class="text-xs text-blue-700 dark:text-blue-300">Existentes (se actualizan)</p>
                            <p class="text-2xl font-semibold text-blue-700 dark:text-blue-300">{{ preview.usuarios_existentes }}</p>
                        </div>
                        <div class="border border-gray-200 dark:border-gray-700 rounded p-3 bg-gray-50 dark:bg-gray-800/40">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Sin cambios (se omiten)</p>
                            <p class="text-2xl font-semibold text-gray-500 dark:text-gray-400">{{ preview.sin_cambios ?? 0 }}</p>
                        </div>
                        <div class="border border-indigo-200 dark:border-indigo-800 rounded p-3 bg-indigo-50 dark:bg-indigo-900/20">
                            <p class="text-xs text-indigo-700 dark:text-indigo-300">Membresías a asignar</p>
                            <p class="text-2xl font-semibold text-indigo-700 dark:text-indigo-300">{{ preview.membresias_a_asignar }}</p>
                        </div>
                        <div class="border border-teal-200 dark:border-teal-800 rounded p-3 bg-teal-50 dark:bg-teal-900/20">
                            <p class="text-xs text-teal-700 dark:text-teal-300">Pagos a registrar</p>
                            <p class="text-2xl font-semibold text-teal-700 dark:text-teal-300">{{ preview.pagos_a_registrar ?? 0 }}</p>
                        </div>
                        <div class="border border-red-200 dark:border-red-800 rounded p-3 bg-red-50 dark:bg-red-900/20">
                            <p class="text-xs text-red-700 dark:text-red-300">Filas con error</p>
                            <p class="text-2xl font-semibold text-red-700 dark:text-red-300">{{ preview.errores }}</p>
                        </div>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                        En las filas a <span class="text-blue-700 dark:text-blue-300 font-medium">Actualizar</span>, los valores resaltados en
                        <span class="rounded bg-amber-100 dark:bg-amber-900/40 px-1.5 py-0.5 text-amber-800 dark:text-amber-200">ámbar (actual → nuevo)</span>
                        indican qué cambia respecto a la membresía actual del usuario.
                    </p>

                    <DataTable
                        :value="preview.filas"
                        stripedRows
                        paginator
                        :rows="20"
                        :rowsPerPageOptions="[10, 20, 50, 100]"
                        tableStyle="min-width: 60rem"
                        class="text-sm"
                    >
                        <Column field="linea" header="Línea" style="width: 5rem"></Column>
                        <Column header="Acción" style="width: 7rem">
                            <template #body="{ data }">
                                <span :class="claseAccion(data)">{{ etiquetaAccion(data) }}</span>
                            </template>
                        </Column>
                        <Column header="Email">
                            <template #body="{ data }">
                                {{ data.datos?.mail || '—' }}
                            </template>
                        </Column>
                        <Column header="Nombre">
                            <template #body="{ data }">
                                {{ data.datos?.name || '—' }}
                            </template>
                        </Column>
                        <Column header="TK" style="width: 8rem">
                            <template #body="{ data }">
                                <span
                                    v-if="data.user_existe && data.cambios?.membresia"
                                    class="inline-flex items-center gap-1 rounded bg-amber-100 dark:bg-amber-900/40 px-1.5 py-0.5"
                                    v-tooltip="'Cambia respecto a la membresía actual'"
                                >
                                    <span class="font-mono text-gray-500 line-through">{{ data.actual?.tk || '—' }}</span>
                                    <i class="pi pi-arrow-right text-[10px] text-amber-600"></i>
                                    <span class="font-mono font-semibold text-amber-800 dark:text-amber-200">{{ data.datos?.tk || '—' }}</span>
                                </span>
                                <span v-else-if="data.datos?.tk" class="font-mono">{{ data.datos.tk }}</span>
                                <span v-else>—</span>
                            </template>
                        </Column>
                        <Column header="Programa" style="width: 6rem">
                            <template #body="{ data }">
                                {{ data.datos?.programa || '—' }}
                            </template>
                        </Column>
                        <Column header="A dist." style="width: 5rem">
                            <template #body="{ data }">
                                <i
                                    v-if="data.programa_a_distancia"
                                    class="fas fa-check text-emerald-600"
                                    v-tooltip="'A distancia'"
                                ></i>
                            </template>
                        </Column>
                        <Column header="Online" style="width: 7rem">
                            <template #body="{ data }">
                                <span
                                    v-if="data.user_existe && data.cambios?.online"
                                    class="inline-flex items-center gap-1 rounded bg-amber-100 dark:bg-amber-900/40 px-1.5 py-0.5"
                                    v-tooltip="'Cambia respecto a la membresía actual'"
                                >
                                    <span class="text-gray-500 line-through">{{ data.actual?.online ? 'Sí' : 'No' }}</span>
                                    <i class="pi pi-arrow-right text-[10px] text-amber-600"></i>
                                    <span class="font-semibold text-amber-800 dark:text-amber-200">{{ data.datos?.online ? 'Sí' : 'No' }}</span>
                                </span>
                                <span v-else-if="data.datos">{{ data.datos.online ? 'Sí' : 'No' }}</span>
                            </template>
                        </Column>
                        <Column header="Suscripción" style="width: 7rem">
                            <template #body="{ data }">
                                <span
                                    v-if="data.user_existe && data.cambios?.suscripcion"
                                    class="inline-flex items-center gap-1 rounded bg-amber-100 dark:bg-amber-900/40 px-1.5 py-0.5"
                                    v-tooltip="'Cambia respecto a la membresía actual'"
                                >
                                    <span class="text-gray-500 line-through">{{ data.actual?.suscripcion ? 'Sí' : 'No' }}</span>
                                    <i class="pi pi-arrow-right text-[10px] text-amber-600"></i>
                                    <span class="font-semibold text-amber-800 dark:text-amber-200">{{ data.datos?.suscripcion ? 'Sí' : 'No' }}</span>
                                </span>
                                <i
                                    v-else-if="data.datos?.suscripcion"
                                    class="fas fa-check text-emerald-600"
                                    v-tooltip="'Marcar como suscripción'"
                                ></i>
                                <span v-else class="text-gray-300">—</span>
                            </template>
                        </Column>
                        <Column header="Pago (mes en curso)" style="width: 13rem">
                            <template #body="{ data }">
                                <div v-if="data.pago?.registrar" class="text-xs leading-5">
                                    <div class="font-semibold text-teal-700 dark:text-teal-300">
                                        <template v-if="data.pago.es_sponsor">Sponsor</template>
                                        <template v-else>$ {{ Number(data.pago.importe).toLocaleString('es-AR') }}</template>
                                    </div>
                                    <div class="text-gray-600 dark:text-gray-400">
                                        <span v-if="data.pago.fecha_pago">{{ data.pago.fecha_pago }}</span>
                                        <span v-if="data.pago.modo"> · {{ data.pago.modo }}</span>
                                        <span v-if="data.pago.info_pago && data.pago.info_pago !== data.pago.modo" class="text-gray-400"> ({{ data.pago.info_pago }})</span>
                                    </div>
                                    <div v-if="data.pago.observaciones" class="text-gray-400 italic">{{ data.pago.observaciones }}</div>
                                </div>
                                <span v-else class="text-xs text-gray-300">—</span>
                            </template>
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

                    <div class="mt-4 flex justify-end gap-3">
                        <button
                            type="button"
                            class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-medium py-2 px-6 rounded inline-flex items-center gap-2"
                            :disabled="procesando || preview.filas_validas === 0"
                            @click="confirmarImportacion"
                        >
                            <i class="pi pi-check"></i>
                            {{ procesando ? 'Importando...' : `Confirmar importación (${preview.filas_validas} filas)` }}
                        </button>
                    </div>
                </div>

                <!-- Card: Resultado -->
                <div v-if="resumen" class="bg-white dark:bg-gray-800 shadow-soft-indigo sm:rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Resultado de la importación
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
                        <div class="border border-emerald-200 dark:border-emerald-800 rounded p-3 bg-emerald-50 dark:bg-emerald-900/20">
                            <p class="text-xs text-emerald-700 dark:text-emerald-300">Usuarios creados</p>
                            <p class="text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ resumen.creados }}</p>
                        </div>
                        <div class="border border-blue-200 dark:border-blue-800 rounded p-3 bg-blue-50 dark:bg-blue-900/20">
                            <p class="text-xs text-blue-700 dark:text-blue-300">Usuarios actualizados</p>
                            <p class="text-2xl font-semibold text-blue-700 dark:text-blue-300">{{ resumen.actualizados }}</p>
                        </div>
                        <div class="border border-gray-200 dark:border-gray-700 rounded p-3 bg-gray-50 dark:bg-gray-800/40">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Sin cambios (omitidos)</p>
                            <p class="text-2xl font-semibold text-gray-500 dark:text-gray-400">{{ resumen.sin_cambios ?? 0 }}</p>
                        </div>
                        <div class="border border-indigo-200 dark:border-indigo-800 rounded p-3 bg-indigo-50 dark:bg-indigo-900/20">
                            <p class="text-xs text-indigo-700 dark:text-indigo-300">Membresías asignadas</p>
                            <p class="text-2xl font-semibold text-indigo-700 dark:text-indigo-300">{{ resumen.membresias_asignadas }}</p>
                        </div>
                        <div class="border border-teal-200 dark:border-teal-800 rounded p-3 bg-teal-50 dark:bg-teal-900/20">
                            <p class="text-xs text-teal-700 dark:text-teal-300">Pagos registrados</p>
                            <p class="text-2xl font-semibold text-teal-700 dark:text-teal-300">{{ resumen.pagos_registrados ?? 0 }}</p>
                        </div>
                        <div class="border border-red-200 dark:border-red-800 rounded p-3 bg-red-50 dark:bg-red-900/20">
                            <p class="text-xs text-red-700 dark:text-red-300">Errores</p>
                            <p class="text-2xl font-semibold text-red-700 dark:text-red-300">{{ resumen.errores }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Dialog
            v-model:visible="formatoDialogVisible"
            header="Formato del archivo CSV"
            :style="{ width: '52rem' }"
            :breakpoints="{ '960px': '90vw' }"
            dismissableMask
            modal
        >
            <div class="space-y-5 text-sm">
                <div>
                    <p class="text-gray-700 dark:text-gray-200">
                        El archivo debe ser un <strong>CSV</strong> con la primera fila como header. Acepta separador <code>,</code> o <code>;</code> y encoding UTF-8 o Latin1 (se detecta automáticamente).
                    </p>
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Header esperado (primera fila)</p>
                    <pre class="text-xs bg-gray-100 dark:bg-gray-900 dark:text-gray-200 p-3 rounded overflow-x-auto">Nombre,Apellido,Activo,TK,Programa,ONLINE,Mail,Teléfonos,Ciudad,Newsletter</pre>
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Detalle de columnas</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm border border-gray-200 dark:border-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="text-left px-3 py-2 border-b border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200">Columna</th>
                                    <th class="text-left px-3 py-2 border-b border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200">Valores aceptados</th>
                                    <th class="text-left px-3 py-2 border-b border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200">Destino</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700 dark:text-gray-200">
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">Nombre</td>
                                    <td class="px-3 py-2">Texto libre</td>
                                    <td class="px-3 py-2">Se concatena con Apellido → <code>users.name</code></td>
                                </tr>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">Apellido</td>
                                    <td class="px-3 py-2">Texto libre</td>
                                    <td class="px-3 py-2">Se concatena con Nombre. También se usa para generar password (<code>{Apellido}2026</code>) en usuarios nuevos</td>
                                </tr>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">Activo</td>
                                    <td class="px-3 py-2"><code>A</code> u otro</td>
                                    <td class="px-3 py-2">Actualmente se ignora (no afecta la importación)</td>
                                </tr>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">TK</td>
                                    <td class="px-3 py-2">Abreviación de Membresía (ej: <code>CLA</code>, <code>COR</code>, <code>BEN</code>)</td>
                                    <td class="px-3 py-2">Lookup en Membresías de la <strong>Entidad seleccionada</strong>, por campo <code>abreviacion</code>. Si no se encuentra, el usuario se importa sin membresía</td>
                                </tr>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">Programa</td>
                                    <td class="px-3 py-2">Abreviación de Programa de Estudio (ej: <code>PG</code>, <code>PF</code>, <code>PFM</code>, <code>PF Dis</code>)</td>
                                    <td class="px-3 py-2">Lookup en Programas de Estudio por <code>abreviacion</code> (o por <code>nombre</code> como fallback) → <code>users.programa_estudio_id</code></td>
                                </tr>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">ONLINE</td>
                                    <td class="px-3 py-2">
                                        <code>SI</code> / <code>NO</code> → <code>membresia_online</code>.<br />
                                        Si el valor termina con asterisco (<code>SI*</code> o <code>NO*</code>), además marca la membresía como <strong>suscripción</strong>.
                                    </td>
                                    <td class="px-3 py-2"><code>membresia_usuario.membresia_online</code> (bool) + <code>membresia_usuario.suscripcion</code> (bool, según asterisco)</td>
                                </tr>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">Mail</td>
                                    <td class="px-3 py-2">Email válido <strong>(obligatorio)</strong></td>
                                    <td class="px-3 py-2"><code>users.email</code>. Si está vacío o inválido, la fila se omite</td>
                                </tr>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">Teléfonos</td>
                                    <td class="px-3 py-2">Texto libre (cualquier formato)</td>
                                    <td class="px-3 py-2"><code>users.telefono</code></td>
                                </tr>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">Ciudad</td>
                                    <td class="px-3 py-2">Texto libre</td>
                                    <td class="px-3 py-2"><code>users.direccion</code></td>
                                </tr>
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-mono text-xs">Newsletter</td>
                                    <td class="px-3 py-2"><code>Si</code> / <code>No</code> / vacío</td>
                                    <td class="px-3 py-2"><code>users.msgxmail</code> (booleano)</td>
                                </tr>
                                <tr class="border-b border-gray-100 dark:border-gray-800 bg-teal-50/50 dark:bg-teal-900/10">
                                    <td class="px-3 py-2 font-mono text-xs">Imp. <span class="text-teal-600">(opcional)</span></td>
                                    <td class="px-3 py-2">Importe del mes (<code>$60,000</code>), <code>Sponsor</code>, o vacío/<code>--</code></td>
                                    <td class="px-3 py-2"><code>estado_cuenta_membresias.importe</code>. Vacío o <code>--</code> ⇒ no se registra pago. <code>Sponsor</code> ⇒ pagado con importe 0</td>
                                </tr>
                                <tr class="border-b border-gray-100 dark:border-gray-800 bg-teal-50/50 dark:bg-teal-900/10">
                                    <td class="px-3 py-2 font-mono text-xs">Día <span class="text-teal-600">(opcional)</span></td>
                                    <td class="px-3 py-2"><code>dd/mm</code> (ej. <code>11/06</code>)</td>
                                    <td class="px-3 py-2"><code>fecha_pago</code>. Sin año ⇒ se asume el año actual</td>
                                </tr>
                                <tr class="border-b border-gray-100 dark:border-gray-800 bg-teal-50/50 dark:bg-teal-900/10">
                                    <td class="px-3 py-2 font-mono text-xs">Modo <span class="text-teal-600">(opcional)</span></td>
                                    <td class="px-3 py-2"><code>Bco</code>, <code>MP</code>, <code>MPS</code>, <code>Efectivo</code>… (puede traer nº de referencia)</td>
                                    <td class="px-3 py-2"><code>modo</code> mapeado al enum (Bco→Transferencia, MPS→Suscripción, MP→Otro, Efectivo→Efectivo, resto→Otro). El texto original queda en <code>info_pago</code></td>
                                </tr>
                                <tr class="bg-teal-50/50 dark:bg-teal-900/10">
                                    <td class="px-3 py-2 font-mono text-xs">Nota <span class="text-teal-600">(opcional)</span></td>
                                    <td class="px-3 py-2">Texto libre</td>
                                    <td class="px-3 py-2"><code>estado_cuenta_membresias.observaciones</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-md bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 px-4 py-3">
                    <p class="text-xs text-blue-700 dark:text-blue-300 font-semibold mb-1">Comportamiento</p>
                    <ul class="list-disc list-inside text-xs text-blue-800 dark:text-blue-200 space-y-1">
                        <li>Si el email ya existe en la BD, se <strong>actualizan</strong> los datos del usuario y se asocia/actualiza la membresía</li>
                        <li>Si el email es nuevo, se crea el usuario con password <code>{Apellido}2026</code> (sin tildes ni espacios)</li>
                        <li>Las filas con email vacío/inválido o email repetido dentro del CSV se omiten y se reportan en el preview</li>
                        <li>Si vienen las columnas de pago (<code>Imp./Día/Modo/Nota</code>), se registra el pago del <strong>mes en curso</strong> en el estado de cuenta de la membresía (uno por usuario+membresía+mes; reimportar no duplica)</li>
                        <li>Toda la importación corre dentro de una transacción: si algo falla a nivel BD, se hace rollback completo</li>
                    </ul>
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Ejemplo de fila</p>
                    <pre class="text-xs bg-gray-100 dark:bg-gray-900 dark:text-gray-200 p-3 rounded overflow-x-auto">Adela,Spinacci,A,CLA,PG,SI,spinacciade@gmail.com,54 9 2323 62-7851,"BsAs, Lujan",Si</pre>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
