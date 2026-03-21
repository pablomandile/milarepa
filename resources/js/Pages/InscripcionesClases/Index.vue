<script>
export default {
    name: 'InscripcionesClasesIndex'
}
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps({
    inscripcionesClases: {
        type: Array,
        required: true,
    },
});

const deleteInscripcionClase = (id) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (!result.isConfirmed) return;

        router.delete(route('inscripciones-clases.destroy', id), {
            onSuccess: () => {
                Swal.fire('¡Eliminado!', 'La inscripción de clase fue eliminada.', 'success');
            },
            onError: () => {
                Swal.fire('Error', 'No se pudo eliminar la inscripción de clase.', 'error');
            },
        });
    });
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Inscripciones de Clases</h1>
        </template>

        <div class="py-12">
            <div class="max-w-[110rem] mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-[108rem] mx-auto">
                    <div class="flex justify-between">
                        <Link
                            :href="route('inscripciones-clases.create')"
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"
                        >
                            REGISTRO DE INSCRIPCIÓN
                        </Link>
                    </div>

                    <div class="mt-4">
                        <DataTable :value="inscripcionesClases" stripedRows paginator :rows="10" :rowsPerPageOptions="[10, 20, 50]" tableStyle="min-width: 65rem">
                            <Column header="Clase">
                                <template #body="slotProps">
                                    {{ slotProps.data.clase?.nombre || '-' }}
                                </template>
                            </Column>
                            <Column header="Participante">
                                <template #body="slotProps">
                                    {{ slotProps.data.user?.name || slotProps.data.guest_user?.name || slotProps.data.nombre_snapshot || '-' }}
                                </template>
                            </Column>
                            <Column header="Email">
                                <template #body="slotProps">
                                    {{ slotProps.data.user?.email || slotProps.data.guest_user?.email || slotProps.data.email_snapshot || '-' }}
                                </template>
                            </Column>
                            <Column field="membresia" header="Membresía" />
                            <Column field="montoApagar" header="Monto Total" />
                            <Column field="pago" header="Pago" />
                            <Column header="Online">
                                <template #body="slotProps">
                                    <span>{{ slotProps.data.online ? 'Sí' : 'No' }}</span>
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('inscripciones-clases.edit', slotProps.data.id)"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            style="display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1;"></i>
                                        </Link>
                                        <button
                                            @click="deleteInscripcionClase(slotProps.data.id)"
                                            class="text-red-600 hover:text-red-800"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1;"></i>
                                        </button>
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
