<script>
    export default {
        name: 'TransportesIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import { ref } from 'vue';
    import { FilterMatchMode } from 'primevue/api';
    import IconField from 'primevue/iconfield';
    import InputIcon from 'primevue/inputicon';
    import InputText from 'primevue/inputtext';

    const { transportes } = defineProps({
        transportes: {
            type: Object,
            required: true
        }
    });

    const filters = ref({
        global: { value: null, matchMode: FilterMatchMode.CONTAINS }
    });
    
    const deleteTransporte = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('transportes.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "El Transporte ha sido eliminado.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema el eliminar el Transporte.", "error");
                },
            });
            }
        });
    };
    
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Transportes</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-4xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create transportes')">
                        <Link :href="route('transportes.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVO TRANSPORTE
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable v-model:filters="filters" :value="transportes.data" stripedRows paginator :rows="5" :rowsPerPageOptions="[5, 10, 20, 50]" tableStyle="min-width: 50rem"
                        :globalFilterFields="['descripcion']">
                            <template #header>
                                <div class="flex justify-end">
                                    <IconField 
                                        iconPosition="left"
                                        class="border border-gray-300 rounded-md px-3 py-2 flex items-center">
                                        <InputIcon class="text-gray-400">
                                            <i class="pi pi-search" />
                                        </InputIcon>
                                        <InputText
                                            v-model="filters['global'].value"
                                            placeholder="Búsqueda"
                                            class="w-full border-0 focus:outline-none focus:ring-0 ml-5"
                                        />
                                    </IconField>
                                </div>
                            </template>
                            <Column field="descripcion" header="Descripción" sortable></Column>
                            <Column field="precio" header="Precio">
                                <template #body="slotProps">
                                    $ {{ parseFloat(slotProps.data.precio).toLocaleString('es-AR', { minimumFractionDigits: 0 }) }}
                                </template>
                            </Column>
                            <Column header="Acciones" class="flex justify-center space-x-2">
                                <template #body="slotProps">
                                    <div class="flex justify-center space-x-2">
                                        <Link
                                            :href="route('transportes.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update transportes')"
                                            v-tooltip="'Editar transporte'">
                                            <i class="pi pi-pencil text-indigo-400 mr-4"></i>
                                        </Link>
                                        <a
                                            @click.prevent="deleteTransporte(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete transportes')"
                                            v-tooltip="'Borrar transporte'">
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