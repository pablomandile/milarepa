<script>
export default {
    name: 'LugaresIndex',
};
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

defineProps({
    lugares: {
        type: Array,
        required: true,
    },
});

const deleteLugar = (id) => {
    Swal.fire({
        title: 'Estas seguro?',
        text: 'Esta accion no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (!result.isConfirmed) return;

        router.delete(route('lugares.destroy', id), {
            onSuccess: () => {
                Swal.fire('Eliminado', 'El lugar ha sido eliminado.', 'success');
            },
            onError: () => {
                Swal.fire('Error', 'Hubo un problema al eliminar el lugar.', 'error');
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
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Lugares</h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-7xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create entidades')">
                        <Link :href="route('lugares.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            NUEVO LUGAR
                        </Link>
                    </div>

                    <div class="mt-4">
                        <DataTable :value="lugares" stripedRows removableSort paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
                            <Column field="nombre" header="Nombre" sortable />
                            <Column field="direccion" header="Direccion" sortable />
                            <Column field="telefono" header="Telefono" sortable />
                            <Column field="email1" header="Correo electronico" sortable />

                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('lugares.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update entidades')"
                                            v-tooltip="'Editar lugar'"
                                            style="display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>

                                        <button
                                            @click="deleteLugar(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete entidades')"
                                            v-tooltip="'Borrar lugar'"
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
    </AppLayout>
</template>

