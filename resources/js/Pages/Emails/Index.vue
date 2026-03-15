<script>
export default {
    name: 'EmailsIndex'
}
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

defineProps({
    emails: {
        type: Array,
        required: true,
    }
});

const deleteEmail = (id) => {
    Swal.fire({
        title: '¿Estas seguro?',
        text: 'Esta accion no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Si, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('emails.destroy', id), {
                onSuccess: () => {
                    Swal.fire('Eliminado!', 'El email ha sido eliminado.', 'success');
                },
                onError: () => {
                    Swal.fire('Error', 'Hubo un problema al eliminar el email.', 'error');
                },
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
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Emails</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-5xl mx-auto">
                    <div class="flex justify-between">
                        <Link :href="route('emails.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            NUEVO EMAIL
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable
                            :value="emails"
                            stripedRows
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            tableStyle="min-width: 50rem"
                        >
                            <Column field="nombre" header="Nombre" />
                            <Column field="descripcion" header="Descripcion" />
                            <Column field="plantilla_archivo" header="Plantilla" />
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('emails.edit', parseInt(slotProps.data.id))"
                                            v-tooltip="'Editar email'"
                                            style="display: flex; align-items: center;"
                                        >
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteEmail(parseInt(slotProps.data.id))"
                                            v-tooltip="'Borrar email'"
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
