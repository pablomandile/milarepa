<script>
    export default {
        name: 'EntidadesIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    
    defineProps({
        entidades: {
            type: Object,
            required: true
        }
    })

    const deleteEntidad = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('entidades.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "La Entidad ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar la Entidad.", "error");
                },
            });
            }
        });
    };
    
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Entidades</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-7xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create entidades')">
                        <Link :href="route('entidades.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVA ENTIDAD
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable :value="entidades.data" stripedRows removableSort paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
                            <Column field="nombre" header="Nombre" sortable></Column>
                            <Column header="Descripción">
                                <template #body="slotProps">
                                    <span v-tooltip="{ content: slotProps.data.descripcion }">
                                        {{ slotProps.data.descripcion.substring(0, 10) }}...
                                    </span>
                                </template>
                            </Column>
                            <Column field="direccion" header="Dirección" sortable></Column>
                            <Column field="telefono" header="Teléfono" sortable></Column>
                            <Column field="email1" header="Correo electrónico" sortable></Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex justify-center space-x-2">
                                        <Link
                                            :href="route('entidades.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update entidades')">
                                            <i class="pi pi-pencil text-indigo-400 mr-2"></i>
                                        </Link>
                                        <a
                                            @click.prevent="deleteEntidad(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete entidades')">
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