<script>
export default {
    name: 'ClasesIndex'
}
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import { ref } from 'vue';

defineProps({
    clases: {
        type: Object,
        required: true
    }
});

const dayLabels = {
    lunes: 'Lun',
    martes: 'Mar',
    miercoles: 'Mie',
    jueves: 'Jue',
    viernes: 'Vie',
    sabado: 'Sab',
    domingo: 'Dom',
};

const formatDias = (diasSemana) => {
    if (!Array.isArray(diasSemana) || diasSemana.length === 0) return '-';

    const ordered = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo']
        .filter(day => diasSemana.includes(day));

    if (ordered.length === 7) return 'Todos';

    return ordered.map(day => dayLabels[day] || day).join(', ');
};

const formatHora = (hora) => {
    if (!hora) return '-';
    return String(hora).slice(0, 5);
};

const formatMes = (mesReferencia) => {
    if (!mesReferencia || !/^\d{4}-(0[1-9]|1[0-2])$/.test(mesReferencia)) return '-';
    const [year, month] = mesReferencia.split('-').map(Number);
    return new Date(year, month - 1, 1).toLocaleDateString('es-AR', { month: 'long', year: 'numeric' });
};

const imageDialogVisible = ref(false);
const selectedImageUrl = ref('');

const openImageDialog = (imageUrl) => {
    if (!imageUrl) return;
    selectedImageUrl.value = imageUrl;
    imageDialogVisible.value = true;
};

const deleteClase = (id) => {
    Swal.fire({
        title: 'Estas seguro?',
        text: 'Esta accion no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('clases.destroy', id), {
                onSuccess: () => Swal.fire('Eliminado!', 'La clase ha sido eliminada.', 'success'),
                onError: () => Swal.fire('Error', 'Hubo un problema al eliminar la clase.', 'error'),
            });
        }
    });
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Clases</h1>
        </template>
        <div class="py-12">
            <div class="max-w-[110rem] mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-[108rem] mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create clases')">
                        <Link :href="route('clases.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            NUEVA CLASE
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable :value="clases.data" stripedRows paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 90rem">
                            <Column header="Imagen">
                                <template #body="slotProps">
                                    <div class="flex items-center justify-center">
                                        <button
                                            v-if="slotProps.data.imagen"
                                            type="button"
                                            class="inline-flex"
                                            v-tooltip="'Ver imagen'"
                                            @click="openImageDialog('/storage/' + slotProps.data.imagen.ruta)"
                                        >
                                            <img
                                                :src="'/storage/' + slotProps.data.imagen.ruta"
                                                alt="Imagen de clase"
                                                class="h-12 w-12 rounded object-cover border border-gray-200"
                                            />
                                        </button>
                                        <span v-else class="text-sm text-gray-400">Sin imagen</span>
                                    </div>
                                </template>
                            </Column>
                            <Column field="nombre" header="Nombre" />
                            <Column header="Mes">
                                <template #body="slotProps">
                                    {{ formatMes(slotProps.data.mes_referencia) }}
                                </template>
                            </Column>
                            <Column field="ciclo.nombre" header="Ciclo" />
                            <Column field="entidad.nombre" header="Entidad" />
                            <Column header="Dias">
                                <template #body="slotProps">
                                    {{ formatDias(slotProps.data.dias_semana) }}
                                </template>
                            </Column>
                            <Column header="Horario">
                                <template #body="slotProps">
                                    {{ formatHora(slotProps.data.horario_desde) }} - {{ formatHora(slotProps.data.horario_hasta) }}
                                </template>
                            </Column>
                            <Column field="maestro.nombre" header="Maestro" />
                            <Column field="coordinador.nombre" header="Coordinador" />
                            <Column field="esquema_precio.nombre" header="Esquema de precios" />
                            <Column header="En calendario">
                                <template #body="slotProps">
                                    <span :class="slotProps.data.mostrar_en_calendario ? 'text-green-600 font-semibold' : 'text-gray-500'">
                                        {{ slotProps.data.mostrar_en_calendario ? 'Si' : 'No' }}
                                    </span>
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="`${route('clases.show-public', { clase: parseInt(slotProps.data.id) })}?return_url=${encodeURIComponent(route('clases.index'))}`"
                                            v-tooltip="'Ver landing publica'"
                                            style="display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-eye" style="font-size: 18px !important; line-height: 1; color: rgb(14, 116, 144);"></i>
                                        </Link>
                                        <Link
                                            :href="route('clases.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update clases')"
                                            v-tooltip="'Editar clase'"
                                            style="display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteClase(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete clases')"
                                            v-tooltip="'Borrar clase'"
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
            header="Imagen de Clase"
            :style="{ width: '720px' }"
        >
            <div class="w-full">
                <img
                    v-if="selectedImageUrl"
                    :src="selectedImageUrl"
                    alt="Imagen de Clase"
                    class="w-full max-h-[70vh] object-contain"
                />
            </div>
        </Dialog>
    </AppLayout>
</template>
