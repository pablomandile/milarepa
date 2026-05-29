<script>
    export default {
        name: 'EsquemaDescuentosIndex'
    }
</script>

<script setup>
    import { computed, ref } from 'vue';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import InputText from 'primevue/inputtext';
    import IconField from 'primevue/iconfield';
    import InputIcon from 'primevue/inputicon';
    import { FilterMatchMode } from 'primevue/api';

    const props = defineProps({
        esquemadescuentos: {
            type: Array,
            required: true
        }
    });

    const esquemasOrdenados = computed(() =>
        [...props.esquemadescuentos].sort((a, b) => {
            const fechaA = a.created_at ? new Date(a.created_at).getTime() : 0;
            const fechaB = b.created_at ? new Date(b.created_at).getTime() : 0;
            if (fechaA !== fechaB) {
                return fechaB - fechaA;
            }
            return Number(b.id || 0) - Number(a.id || 0);
        })
    );

    // Controla qué filas de la tabla principal están expandidas
    const expandedRows = ref([]);

    const filters = ref({
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    });

    const deleteEsquemaDescuento = (id) => {
    Swal.fire({
        title: "¿Estás seguro?",
        text: "Esta acción no se puede deshacer.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
        router.delete(route('esquemadescuentos.destroy', id), {
                onSuccess: () => {
                Swal.fire("¡Eliminado!", "El Esquema de Descuento ha sido eliminado.", "success");
                },
                onError: () => {
                Swal.fire("Error", "Hubo un problema al eliminar el Esquema de Descuento.", "error");
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
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Esquemas de Descuentos</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-6xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create esquema_descuentos')">
                        <Link :href="route('esquemadescuentos.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded" > 
                            NUEVO ESQUEMA DE DESCUENTOS
                        </Link>
                    </div>
                    <div class="mt-4">
                        <DataTable
                            :value="esquemasOrdenados"
                            v-model:filters="filters"
                            :globalFilterFields="['nombre']"
                            stripedRows
                            paginator
                            :rows="10"
                            v-model:expandedRows="expandedRows"
                            dataKey="id"
                            :rowsPerPageOptions="[5, 10, 20, 50]"
                            tableStyle="min-width: 50rem"
                        >
                            <template #header>
                                <div class="flex justify-end">
                                    <IconField iconPosition="right">
                                        <InputIcon>
                                            <i class="pi pi-search" />
                                        </InputIcon>
                                        <InputText v-model="filters['global'].value" placeholder="Buscar..." />
                                    </IconField>
                                </div>
                            </template>
                            <Column expander style="width: 5rem" />
                            <Column field="nombre" header="Nombre" sortable></Column>
                            <Column header="Acciones" class="flex justify-center space-x-2">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('esquemadescuentos.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update esquema_descuentos')"
                                            v-tooltip="'Editar esquema'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deleteEsquemaDescuento(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete esquema_descuentos')"
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

                                    <!-- Columna Boton de Pago -->
                                    <Column header="Boton de Pago">
                                        <template #body="{ data: mem }">
                                            {{ mem.boton_pago ? mem.boton_pago.nombre : '—' }}
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
