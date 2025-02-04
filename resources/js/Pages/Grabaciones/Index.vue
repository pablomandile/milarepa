<script>
    export default {
        name: 'GrabacionesIndex'
    }
</script>

<script setup>
    import { ref } from 'vue';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    
    defineProps({
        grabaciones: {
            type: Array,
            required: true
        }
    })

    // Controla qué filas de la tabla principal están expandidas
    const expandedRows = ref([]);

    const deleteGrabacion = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('grabaciones.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "La Grabación ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar la Grabación.", "error");
                },
            });
            }
        });
    };
    
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Grabaciones</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-6xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create grabaciones')">
                        <Link :href="route('grabaciones.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVA GRABACIÓN
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable 
                            :value="grabaciones" 
                            stripedRows 
                            paginator 
                            :rows="10" 
                            v-model:expandedRows="expandedRows"
                            dataKey="id"
                            :rowsPerPageOptions="[5, 10, 20, 50]" 
                            tableStyle="min-width: 50rem"
                        >
                            <Column expander style="width: 5rem" />
                            <Column field="nombre" header="Nombre"></Column>
                            <Column header="Acciones" class="flex justify-center space-x-2">
                                <template #body="slotProps">
                                    <div class="flex justify-center space-x-2">
                                        <Link
                                            :href="route('grabaciones.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update grabaciones')"
                                            v-tooltip="'Editar grabación'">
                                            <i class="pi pi-pencil text-indigo-400 mr-4"></i>
                                        </Link>
                                        <a
                                            @click.prevent="deleteGrabacion(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete grabaciones')"
                                            class="text-red-500 cursor-pointer"
                                            v-tooltip="'Borrar grabación'">
                                            <i class="pi pi-trash text-red-300"></i>
                                        </a>
                                    </div>
                                </template>
                            </Column>

                            <template #expansion="{ data }">
                                <DataTable 
                                    :value="data.linksgrabacion"
                                    class="mt-3"
                                    stripedRows
                                    tableStyle="min-width: 50rem"
                                >
                                    <Column field="nombre" header="Nombre"></Column>
                                    <Column field="link" header="Link"></Column>
                                </DataTable>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>