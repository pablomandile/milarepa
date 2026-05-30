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
                            :rows="5"
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
    </AppLayout>
</template>
