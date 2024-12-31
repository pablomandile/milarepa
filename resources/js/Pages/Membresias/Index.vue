<script>
    export default {
        name: 'MembresiasIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    
    defineProps({
        membresias: {
            type: Object,
            required: true
        }
    });

    const deleteMembresia = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('membresias.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "La Membresía ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar la Membresía.", "error");
                },
            });
            }
        });
    };
    
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Membresias</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-5xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create membresias')">
                        <Link :href="route('membresias.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVA MEMBRESÍA
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable :value="membresias.data" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem">
                            <Column field="nombre" header="Nombre"></Column>
                            <Column field="descripcion" header="Descripción"></Column>
                            <Column field="entidad" header="Entidad">
                                <template #body="slotProps">
                                    {{ slotProps.data.entidad ? slotProps.data.entidad.nombre : '—' }}
                                </template>
                            </Column>
                            <Column header="Acciones">
                                <template #body="slotProps">
                                    <div class="flex space-x-2">
                                        <Link
                                            :href="route('membresias.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update membresias')"
                                            v-tooltip="'Editar membresía'">
                                            <i class="pi pi-pencil text-indigo-400 mr-6"></i>
                                        </Link>
                                        <a
                                            @click.prevent="deleteMembresia(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete membresias')"
                                            v-tooltip="'Borrar membresía'">
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