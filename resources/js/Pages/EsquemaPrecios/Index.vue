<script>
    export default {
        name: 'EsquemaPreciosIndex'
    }
</script>

<script setup>
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    
    defineProps({
        esquemaprecios: {
            type: Object,
            required: true
        }
    })

    const deleteEsquemaPrecio = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('esquemaprecios.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "El EsquemaPrecio ha sido eliminada.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar el EsquemaPrecio.", "error");
                },
            });
            }
        });
    };
    
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Esquemas de Precios</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-6xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create esquema_precios')">
                        <Link :href="route('esquemaprecios.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVO ESQUEMA DE PRECIOS
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable :value="esquemaprecios.data" stripedRows paginator :rows="10" 
                        :rowsPerPageOptions="[5, 10, 20, 50]" rowGroupMode="rowspan" groupRowsBy="nombre" 
                        sortMode="single" sortField="nombre" :sortOrder="1" tableStyle="min-width: 50rem">
                            <Column field="nombre" header="Nombre"></Column>
                            <Column field="membresia" header="Membresia / Entidad">
                                <template #body="slotProps">
                                    {{ slotProps.data.membresia ? slotProps.data.membresia.nombre + "-" + slotProps.data.membresia.entidad.abreviacion : '—' }}
                                </template>
                            </Column>
                            <Column field="precio" header="Precio">
                                <template #body="slotProps">
                                    $ {{ parseFloat(slotProps.data.precio).toLocaleString('es-AR', { minimumFractionDigits: 0 }) }}
                                </template>
                            </Column>
                            <Column field="moneda" header="Moneda">
                                <template #body="slotProps">
                                    {{ slotProps.data.moneda ? slotProps.data.moneda.nombre : '—' }}
                                </template>
                            </Column>
                            <Column header="Acciones" class="flex justify-center space-x-2">>
                                <template #body="slotProps">
                                    <div class="flex justify-center space-x-2">
                                        <Link
                                            :href="route('esquemaprecios.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update esquema_precios')">
                                            <i class="pi pi-pencil text-indigo-400 mr-2"></i>
                                        </Link>
                                        <a
                                            @click.prevent="deleteEsquemaPrecio(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete esquema_precios')"
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