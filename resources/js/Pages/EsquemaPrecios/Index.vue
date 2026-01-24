<script>
    export default {
        name: 'EsquemaPreciosIndex'
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
        esquemaprecios: {
            type: Array,
            required: true
        }
    })

    // Controla qué filas de la tabla principal están expandidas
    const expandedRows = ref([]);

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

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

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
                        <DataTable 
                        :value="esquemaprecios" 
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
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('esquemaprecios.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update esquema_precios')"
                                            v-tooltip="'Editar esquema'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteEsquemaPrecio(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete esquema_precios')"
                                            v-tooltip="'Borrar esquema'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1; color: rgb(239, 68, 68);"></i>
                                        </button>
                                    </div>
                                </template>
                            </Column>

                            <template #expansion="{ data }">
                                <DataTable 
                                    :value="data.membresias"
                                    class="mt-3"
                                    stripedRows
                                    tableStyle="min-width: 50rem"
                                >
                                    <!-- Columna Membresía / Entidad -->
                                    <Column header="Membresía - Entidad">
                                        <template #body="{ data: mem }">
                                            {{ mem.membresia
                                                ? mem.membresia.nombre + ' - ' + mem.membresia.entidad.abreviacion
                                                : '—'
                                            }}
                                        </template>
                                    </Column>

                                    <!-- Columna Precio -->
                                    <Column header="Precio">
                                        <template #body="{ data: mem }">
                                            $ {{ parseFloat(mem.precio).toLocaleString('es-AR', { minimumFractionDigits: 0 }) }}
                                        </template>
                                    </Column>

                                    <!-- Columna Moneda -->
                                    <Column header="Moneda">
                                        <template #body="{ data: mem }">
                                            {{ mem.moneda ? mem.moneda.nombre : '—' }}
                                        </template>
                                    </Column>

                                </DataTable>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>