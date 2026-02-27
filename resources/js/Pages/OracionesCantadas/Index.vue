<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';

defineProps({
    oracionesCantadas: {
        type: Object,
        required: true,
    },
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
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Oraciones Cantadas</h1>
        </template>

        <div class="py-12">
            <div class="max-w-[96rem] mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-[92rem] mx-auto">
                    <div class="flex justify-between">
                        <Link :href="route('oracionescantadas.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            CREAR ORACION CANTADA
                        </Link>
                    </div>

                    <div class="mt-4">
                        <DataTable :value="oracionesCantadas.data" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 68rem">
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
                                                class="h-12 w-12 rounded object-cover border border-gray-200"
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
