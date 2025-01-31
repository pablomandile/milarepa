<script>
    export default {
        name: 'NovedadesGestion'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    
    defineProps({
        novedades: {
            type: Object,
            required: true
        }
    })

    const deleteNovedad = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('novedades.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "La Novedad ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar la Novedad.", "error");
                },
            });
            }
        });
    };
    
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Novedades</h1>
        </template>
        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-4xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create novedades')">
                        <Link :href="route('novedades.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVA NOVEDAD
                        </Link>
                        <Link 
                            :href="route('novedades.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable :value="novedades" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
                            <Column field="nombre" header="Nombre" class="w-1/5"></Column>
                            <Column field="fecha" header="Fecha" class="w-1/5"></Column>
                            <Column field="descripcion" header="Descripción" class="w-3/5"></Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex space-x-2">
                                        <Link
                                            :href="route('novedades.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update novedades')"
                                            v-tooltip="'Editar novedad'">
                                            <i class="pi pi-pencil text-indigo-400 mr-4"></i>
                                        </Link>
                                        <a
                                            @click.prevent="deleteNovedad(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete novedades')"
                                            v-tooltip="'Borrar novedad'">
                                            <i class="pi pi-trash cursor-pointer text-red-300"></i>
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