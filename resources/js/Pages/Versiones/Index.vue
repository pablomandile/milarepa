<script>
export default {
    name: 'VersionesIndex'
}
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import Swal from "sweetalert2";
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

defineProps({
    versiones: {
        type: Object,
        required: true
    }
})

const deleteVersion = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('versiones.destroy', id), {
                onSuccess: () => {
                    Swal.fire("¡Eliminado!", "La versión ha sido eliminada.", "success");
                },
                onError: () => {
                    Swal.fire("Error", "Hubo un problema al eliminar la versión.", "error");
                },
            });
        }
    });
};
</script>

<template>
    <AppLayout title="Versiones del Sistema">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Versiones del Sistema</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <!-- Header con botón de crear -->
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-800">
                                <i class="fas fa-code-branch mr-2 text-indigo-600"></i>
                                Gestión de Versiones
                            </h2>
                            <Link 
                                v-if="$page.props.user.permissions.includes('create versiones')"
                                :href="route('versiones.create')" 
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition">
                                <i class="fas fa-plus mr-2"></i>
                                Nueva Versión
                            </Link>
                        </div>

                        <!-- DataTable -->
                        <DataTable 
                            :value="versiones" 
                            :paginator="true" 
                            :rows="10"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} versiones"
                            responsiveLayout="scroll"
                            stripedRows
                            class="p-datatable-sm">
                            
                            <Column field="version" header="Versión" :sortable="true">
                                <template #body="slotProps">
                                    <span class="font-semibold text-lg text-indigo-700">
                                        {{ slotProps.data.version }}
                                    </span>
                                </template>
                            </Column>
                            
                            <Column field="created_at" header="Fecha de Creación" :sortable="true">
                                <template #body="slotProps">
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-calendar-alt mr-2 text-indigo-500"></i>
                                        {{ new Date(slotProps.data.created_at).toLocaleDateString('es-ES', { 
                                            year: 'numeric', 
                                            month: 'long', 
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        }) }}
                                    </div>
                                </template>
                            </Column>

                            <Column header="Acciones" class="flex justify-center space-x-2">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('versiones.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update versiones')"
                                            v-tooltip="'Editar versión'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteVersion(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete versiones')"
                                            v-tooltip="'Borrar versión'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1; color: rgb(239, 68, 68);"></i>
                                        </button>
                                    </div>
                                </template>
                            </Column>

                            <template #empty>
                                <div class="text-center py-8">
                                    <i class="fas fa-inbox text-gray-400 text-5xl mb-3"></i>
                                    <p class="text-gray-500 text-lg">No hay versiones registradas</p>
                                </div>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
