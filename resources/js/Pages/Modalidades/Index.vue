<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Swal from "sweetalert2";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

defineProps({
    modalidades: {
        type: Object,
        required: true,
    },
});

const deleteModalidad = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('modalidades.destroy', id), {
                onSuccess: () => {
                    Swal.fire("¡Eliminado!", "La modalidad ha sido eliminada.", "success");
                },
                onError: () => {
                    Swal.fire("Error", "Hubo un problema al eliminar la modalidad.", "error");
                },
            });
        }
    });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Modalidad</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-4xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create modalidades')">
                        <Link :href="route('modalidades.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"> 
                            CREAR MODALIDAD
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable :value="modalidades.data" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
                            <Column field="nombre" header="Nombre"></Column>

                            <Column header="" headerClass="text-right" >
                                <template #body="slotProps">
                                    <div class="flex justify-end space-x-2">
                                        <Link
                                            :href="route('modalidades.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update modalidades')"
                                            class="text-indigo-500">
                                            <i class="pi pi-pencil text-indigo-400 mr-2"></i>
                                        </Link>
                                        <a
                                            @click.prevent="deleteModalidad(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete modalidades')"
                                            class="text-red-500 cursor-pointer">
                                            <i class="pi pi-trash text-red-300"></i>
                                        </a>
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
