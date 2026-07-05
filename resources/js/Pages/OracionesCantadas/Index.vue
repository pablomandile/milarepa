<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { FilterMatchMode } from 'primevue/api';

const props = defineProps({
    oracionesCantadas: {
        type: Array,
        required: true,
    },
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const oracionesFiltradasMobile = computed(() => {
    const term = (filters.value.global.value || '').toString().trim().toLowerCase();
    if (!term) return props.oracionesCantadas;
    return props.oracionesCantadas.filter((o) => {
        const campos = [o.nombre, o.periodicidad, String(o.hora ?? ''), String(o.dia ?? '')];
        return campos.some((v) => String(v ?? '').toLowerCase().includes(term));
    });
});

const deleteOracionCantada = (id) => {
    Swal.fire({
        title: 'Estas seguro?',
        text: 'Esta accion no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('oracionescantadas.destroy', id), {
                onSuccess: () => Swal.fire('Eliminado!', 'La oracion cantada ha sido eliminada.', 'success'),
                onError: () => Swal.fire('Error', 'Hubo un problema al eliminar la oracion cantada.', 'error'),
            });
        }
    });
};

const dayLabels = {
    lunes: 'Lun',
    martes: 'Mar',
    miercoles: 'Mie',
    jueves: 'Jue',
    viernes: 'Vie',
    sabado: 'Sab',
    domingo: 'Dom',
};

const formatDias = (row) => {
    if (row.periodicidad === 'Mensual') {
        return row.dia ?? '-';
    }

    if (!Array.isArray(row.dias_semana) || row.dias_semana.length === 0) {
        return '-';
    }

    const sorted = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo']
        .filter(day => row.dias_semana.includes(day));

    if (sorted.length === 7) return 'Todos';

    return sorted.map(day => dayLabels[day] || day).join(', ');
};

const formatHora = (hora) => {
    if (!hora) return '-';
    const value = String(hora).slice(0, 5);
    return `${value} hs.`;
};

const imageDialogVisible = ref(false);
const selectedImageUrl = ref('');

const openImageDialog = (imageUrl) => {
    if (!imageUrl) return;
    selectedImageUrl.value = imageUrl;
    imageDialogVisible.value = true;
};

// --- Dialog de configuraciones particulares por mes ---
const mesLabels = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const ordenDias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
const diasLabelFull = {
    lunes: 'Lunes', martes: 'Martes', miercoles: 'Miercoles', jueves: 'Jueves',
    viernes: 'Viernes', sabado: 'Sabado', domingo: 'Domingo',
};

const detailsDialogVisible = ref(false);
const selectedOracion = ref(null);

const tieneDetalles = (oracion) =>
    (Array.isArray(oracion?.configuracion_por_mes) && oracion.configuracion_por_mes.length > 0) ||
    (Array.isArray(oracion?.excepciones_por_fecha) && oracion.excepciones_por_fecha.length > 0);

const formatFechaLabel = (value) => {
    if (!value) return '-';
    const [y, m, d] = String(value).split('-').map(Number);
    if (!y || !m || !d) return String(value);
    const raw = new Date(y, m - 1, d).toLocaleDateString('es-AR', {
        weekday: 'short', day: '2-digit', month: '2-digit', year: 'numeric',
    });
    return raw.charAt(0).toUpperCase() + raw.slice(1);
};

const configuracionesDe = (oracion) => {
    const arr = Array.isArray(oracion?.configuracion_por_mes) ? oracion.configuracion_por_mes : [];
    return arr
        .filter((c) => c && Number(c.mes) >= 1 && Number(c.mes) <= 12)
        .slice()
        .sort((a, b) => Number(a.mes) - Number(b.mes))
        .map((c) => {
            const esDiaria = c.periodicidad === 'Diaria';
            const horaGeneral = c.hora ? String(c.hora).slice(0, 5) : null;
            const horarios = c.horarios_por_dia && typeof c.horarios_por_dia === 'object' ? c.horarios_por_dia : {};
            const dias = Array.isArray(c.dias_semana) ? ordenDias.filter((d) => c.dias_semana.includes(d)) : [];

            return {
                mesLabel: mesLabels[Number(c.mes)] || `Mes ${c.mes}`,
                periodicidad: c.periodicidad || '-',
                esDiaria,
                diaLabel: !esDiaria ? (c.dia ?? '-') : null,
                horaLabel: horaGeneral ? `${horaGeneral} hs.` : '-',
                dias: dias.map((d) => ({
                    label: diasLabelFull[d] || d,
                    hora: horarios[d] ? String(horarios[d]).slice(0, 5) : horaGeneral,
                })),
            };
        });
};

const configuracionesSeleccionadas = computed(() =>
    selectedOracion.value ? configuracionesDe(selectedOracion.value) : []
);

const excepcionesDe = (oracion) => {
    const arr = Array.isArray(oracion?.excepciones_por_fecha) ? oracion.excepciones_por_fecha : [];
    return arr
        .filter((e) => e && e.fecha)
        .slice()
        .sort((a, b) => String(a.fecha).localeCompare(String(b.fecha)))
        .map((e) => ({
            fechaLabel: formatFechaLabel(e.fecha),
            hora: e.hora ? String(e.hora).slice(0, 5) : null,
            mensaje: e.mensaje || null,
        }));
};

const excepcionesSeleccionadas = computed(() =>
    selectedOracion.value ? excepcionesDe(selectedOracion.value) : []
);

const openDetailsDialog = (oracion) => {
    selectedOracion.value = oracion;
    detailsDialogVisible.value = true;
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';

:deep(.p-datatable .p-datatable-thead > tr > th .p-column-header-content) {
    white-space: nowrap;
}
</style>

<template>
    <AppLayout title="Oraciones Cantadas">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Oraciones Cantadas</h1>
        </template>

        <div class="py-12">
            <div class="max-w-[96rem] mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-[92rem] mx-auto">
                    <div class="flex justify-between">
                        <Link :href="route('oracionescantadas.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            CREAR ORACION CANTADA
                        </Link>
                    </div>

                    <!-- Buscador móvil -->
                    <div v-if="oracionesCantadas.length > 0" class="sm:hidden mt-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="oracionesFiltradasMobile.length > 0" class="space-y-4 sm:hidden mt-4">
                        <div
                            v-for="oracion in oracionesFiltradasMobile"
                            :key="oracion.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-center gap-3">
                                    <button
                                        v-if="oracion.imagen"
                                        type="button"
                                        class="inline-flex flex-shrink-0"
                                        @click="openImageDialog(oracion.imagen)"
                                    >
                                        <img
                                            :src="oracion.imagen"
                                            alt="Imagen oracion cantada"
                                            class="h-14 w-14 rounded object-cover border border-gray-200 dark:border-gray-700"
                                        />
                                    </button>
                                    <div v-else class="h-14 w-14 rounded bg-gray-100 dark:bg-gray-900 flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-music text-2xl text-gray-400"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-base font-semibold text-gray-800 dark:text-gray-100 break-words">{{ oracion.nombre }}</p>
                                        <p v-if="oracion.periodicidad" class="text-sm text-gray-600 dark:text-gray-400">{{ oracion.periodicidad }}</p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Hora</span>
                                        <span class="text-right">{{ formatHora(oracion.hora) }}</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-3 text-sm">
                                        <span class="text-gray-500 flex-shrink-0">Día / Días</span>
                                        <span class="text-right">{{ formatDias(oracion).toString() }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Online</span>
                                        <span :class="(oracion.stream_id || oracion.stream?.id) ? 'text-green-600 font-semibold' : 'text-gray-500'">
                                            {{ (oracion.stream_id || oracion.stream?.id) ? 'Sí' : 'No' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">En calendario</span>
                                        <span :class="oracion.mostrar_en_calendario ? 'text-green-600 font-semibold' : 'text-gray-500'">
                                            {{ oracion.mostrar_en_calendario ? 'Sí' : 'No' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <button
                                        v-if="tieneDetalles(oracion)"
                                        @click="openDetailsDialog(oracion)"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-amber-100 text-amber-700 px-3 text-xs font-semibold hover:bg-amber-200 transition"
                                        title="Ver detalles"
                                    >
                                        <i class="fas fa-calendar-days"></i>
                                        <span>Detalles</span>
                                    </button>
                                    <Link
                                        :href="`${route('oracionescantadas.show-public', parseInt(oracion.id))}?return_url=${encodeURIComponent(route('oracionescantadas.index'))}`"
                                        target="_blank"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-blue-100 text-blue-700 px-3 text-xs font-semibold hover:bg-blue-200 transition"
                                        title="Ver landing pública"
                                    >
                                        <i class="fas fa-eye"></i>
                                        <span>Ver landing</span>
                                    </Link>
                                    <Link
                                        :href="route('oracionescantadas.edit', parseInt(oracion.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar oración cantada"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </Link>
                                    <button
                                        @click="deleteOracionCantada(parseInt(oracion.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar oración cantada"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="oracionesCantadas.length > 0" class="sm:hidden mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>

                    <!-- Tabla desktop -->
                    <div class="mt-4 hidden sm:block">
                        <DataTable
                            :value="oracionesCantadas"
                            v-model:filters="filters"
                            :globalFilterFields="['nombre', 'periodicidad']"
                            stripedRows
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            tableStyle="min-width: 68rem"
                        >
                            <template #header>
                                <div class="flex justify-end">
                                    <IconField iconPosition="right">
                                        <InputIcon>
                                            <i class="pi pi-search" />
                                        </InputIcon>
                                        <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                                    </IconField>
                                </div>
                            </template>
                            <Column field="imagen" header="Imagen">
                                <template #body="slotProps">
                                    <div class="flex items-center justify-center">
                                        <button
                                            v-if="slotProps.data.imagen"
                                            type="button"
                                            class="inline-flex"
                                            v-tooltip="'Ver imagen'"
                                            @click="openImageDialog(slotProps.data.imagen)"
                                        >
                                            <img
                                                :src="slotProps.data.imagen"
                                                alt="Imagen oracion cantada"
                                                class="h-12 w-12 rounded object-cover border border-gray-200 dark:border-gray-700"
                                            />
                                        </button>
                                        <span v-else class="text-sm text-gray-400">Sin imagen</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="nombre" header="Nombre" />
                            <Column field="periodicidad" header="Periodicidad" />
                            <Column header="Hora">
                                <template #body="slotProps">
                                    <span>{{ formatHora(slotProps.data.hora) }}</span>
                                </template>
                            </Column>
                            <Column header="Dia / Dias">
                                <template #body="slotProps">
                                    <span>{{ formatDias(slotProps.data) }}</span>
                                </template>
                            </Column>
                            <Column header="ONL">
                                <template #body="slotProps">
                                    <span :class="(slotProps.data.stream_id || slotProps.data.stream?.id) ? 'text-green-600 font-semibold' : 'text-gray-500'">
                                        {{ (slotProps.data.stream_id || slotProps.data.stream?.id) ? 'Si' : 'No' }}
                                    </span>
                                </template>
                            </Column>
                            <Column header="En calendario">
                                <template #body="slotProps">
                                    <span :class="slotProps.data.mostrar_en_calendario ? 'text-green-600 font-semibold' : 'text-gray-500'">
                                        {{ slotProps.data.mostrar_en_calendario ? 'Si' : 'No' }}
                                    </span>
                                </template>
                            </Column>

                            <Column header="Acciones" class="flex justify-center space-x-2">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <button
                                            v-if="tieneDetalles(slotProps.data)"
                                            @click="openDetailsDialog(slotProps.data)"
                                            v-tooltip="'Ver detalles (config. por mes y excepciones)'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-calendar-days" style="font-size: 18px !important; line-height: 1; color: rgb(217, 119, 6);"></i>
                                        </button>
                                        <Link
                                            :href="`${route('oracionescantadas.show-public', parseInt(slotProps.data.id))}?return_url=${encodeURIComponent(route('oracionescantadas.index'))}`"
                                            v-tooltip="'Ver landing publica'"
                                            target="_blank"
                                            style="display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-eye" style="font-size: 18px !important; line-height: 1; color: rgb(59, 130, 246);"></i>
                                        </Link>
                                        <Link
                                            :href="route('oracionescantadas.edit', parseInt(slotProps.data.id))"
                                            v-tooltip="'Editar oracion cantada'"
                                            style="display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteOracionCantada(parseInt(slotProps.data.id))"
                                            v-tooltip="'Borrar oracion cantada'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1; color: rgb(239, 68, 68);"></i>
                                        </button>
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>

        <Dialog
            v-model:visible="imageDialogVisible"
            modal
            header="Imagen Oracion Cantada"
            :style="{ width: '720px' }"
        >
            <div class="w-full">
                <img
                    v-if="selectedImageUrl"
                    :src="selectedImageUrl"
                    alt="Imagen Oracion Cantada"
                    class="w-full max-h-[70vh] object-contain"
                />
            </div>
        </Dialog>

        <Dialog
            v-model:visible="detailsDialogVisible"
            modal
            :header="selectedOracion ? `Detalles - ${selectedOracion.nombre}` : 'Detalles'"
            :style="{ width: '640px' }"
            :breakpoints="{ '640px': '95vw' }"
        >
            <div class="space-y-6">
                <!-- Configuracion personalizada por mes -->
                <div>
                    <h3 class="mb-2 text-sm font-semibold text-gray-800 dark:text-gray-100">Configuracion personalizada por mes</h3>
                    <div v-if="configuracionesSeleccionadas.length" class="grid gap-3 sm:grid-cols-2">
                        <div
                            v-for="(cfg, i) in configuracionesSeleccionadas"
                            :key="`cfg-${i}`"
                            class="rounded-lg border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-900 p-3"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-semibold text-slate-800 dark:text-gray-100">{{ cfg.mesLabel }}</span>
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">{{ cfg.periodicidad }}</span>
                            </div>

                            <div v-if="!cfg.esDiaria" class="mt-2 space-y-0.5 text-sm text-slate-700 dark:text-gray-300">
                                <div><span class="font-medium">Dia del mes:</span> {{ cfg.diaLabel }}</div>
                                <div><span class="font-medium">Hora:</span> {{ cfg.horaLabel }}</div>
                            </div>

                            <div v-else class="mt-2 text-sm text-slate-700 dark:text-gray-300">
                                <div class="mb-1 font-medium">Dias:</div>
                                <div v-if="cfg.dias.length" class="space-y-0.5">
                                    <div v-for="d in cfg.dias" :key="`${i}-${d.label}`">
                                        <span class="font-medium">{{ d.label }}:</span> {{ d.hora ? `${d.hora} hs.` : '-' }}
                                    </div>
                                </div>
                                <div v-else class="text-slate-500">-</div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-500 dark:text-gray-400">Sin configuraciones por mes.</p>
                </div>

                <!-- Excepciones por fecha -->
                <div>
                    <h3 class="mb-2 text-sm font-semibold text-gray-800 dark:text-gray-100">Excepciones por fecha</h3>
                    <div v-if="excepcionesSeleccionadas.length" class="space-y-2">
                        <div
                            v-for="(exc, i) in excepcionesSeleccionadas"
                            :key="`exc-${i}`"
                            class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 dark:border-gray-700 bg-slate-50 dark:bg-gray-900 p-3 text-sm"
                        >
                            <span class="font-semibold text-slate-800 dark:text-gray-100">{{ exc.fechaLabel }}</span>
                            <span v-if="exc.hora" class="text-slate-600 dark:text-gray-300">{{ exc.hora }} hs.</span>
                            <span
                                v-if="exc.mensaje"
                                class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
                            >
                                <i class="pi pi-exclamation-triangle text-xs"></i>{{ exc.mensaje }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-500 dark:text-gray-400">Sin excepciones por fecha.</p>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
