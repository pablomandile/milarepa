<script>
    export default {
        name: 'ComidasIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    
    defineProps({
        comidas: {
            type: Object,
            required: true
        }
    })

    const deleteComida = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('comidas.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "La Comida ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar la Comida.", "error");
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
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Comidas</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-4xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create comidas')">
                        <Link :href="route('comidas.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVA COMIDA
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable :value="comidas.data" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
                            <Column field="nombre" header="Nombre"></Column>
                            <Column field="descripcion" header="Descripción"></Column>
                            <Column field="precio" header="Precio"></Column>
                            <Column header="Vegano">
                                <template #body="slotProps">
                                    <div class="flex justify-center">
                                    <!-- Muestra el icono "check" en verde si es true, "times" en rojo si es false -->
                                    <i v-if="slotProps.data.vegano" class="pi pi-check text-green-500"></i>
                                    <i v-else class="pi pi-times text-red-500"></i>
                                    </div>
                                </template>
                            </Column>
                            <Column header="Celíaco">
                                <template #body="slotProps">
                                    <div class="flex justify-center">
                                    <!-- Muestra el icono "check" en verde si es true, "times" en rojo si es false -->
                                    <i v-if="slotProps.data.celiaco" class="pi pi-check text-green-500"></i>
                                    <i v-else class="pi pi-times text-red-500"></i>
                                    </div>
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('comidas.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update comidas')"
                                            v-tooltip="'Editar comida'"
                                            class="text-indigo-600 hover:text-indigo-800"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1;"></i>
                                        </Link>
                                        <button
                                            @click="deleteComida(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete comidas')"
                                            v-tooltip="'Borrar comida'"
                                            class="text-red-600 hover:text-red-800"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
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