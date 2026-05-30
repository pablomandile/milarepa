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
    const expandedCardIds = ref([]);

    const isCardExpanded = (id) => expandedCardIds.value.includes(id);
    const toggleCardExpanded = (id) => {
        const idx = expandedCardIds.value.indexOf(id);
        if (idx === -1) expandedCardIds.value.push(id);
        else expandedCardIds.value.splice(idx, 1);
    };

    const filters = ref({
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    });

    const esquemasFiltradosMobile = computed(() => {
        const term = (filters.value.global.value || '').toString().trim().toLowerCase();
        if (!term) return esquemasOrdenados.value;
        return esquemasOrdenados.value.filter((e) => String(e.nombre ?? '').toLowerCase().includes(term));
    });

    const formatPrecio = (v) => `$ ${parseFloat(v || 0).toLocaleString('es-AR', { minimumFractionDigits: 0 })}`;

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
                    <!-- Buscador móvil -->
                    <div v-if="esquemasOrdenados.length > 0" class="sm:hidden mt-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="esquemasFiltradosMobile.length > 0" class="space-y-4 sm:hidden mt-4">
                        <div
                            v-for="esquema in esquemasFiltradosMobile"
                            :key="esquema.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-percent text-2xl text-indigo-600"></i>
                                    <p class="text-base font-semibold text-gray-800 dark:text-gray-100 flex-1 min-w-0 break-words">{{ esquema.nombre }}</p>
                                </div>

                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    @click="toggleCardExpanded(esquema.id)"
                                >
                                    <span>{{ isCardExpanded(esquema.id) ? 'Ocultar membresías' : 'Ver membresías' }} ({{ (esquema.membresias || []).length }})</span>
                                    <i class="pi" :class="isCardExpanded(esquema.id) ? 'pi-chevron-up' : 'pi-chevron-down'"></i>
                                </button>

                                <div v-if="isCardExpanded(esquema.id)" class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-3">
                                    <div v-if="(esquema.membresias || []).length === 0" class="text-sm text-gray-500">Sin membresías cargadas.</div>
                                    <div v-else class="space-y-3">
                                        <div v-for="mem in esquema.membresias" :key="mem.id" class="border-b border-gray-200 dark:border-gray-700 last:border-b-0 pb-2 last:pb-0">
                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                                {{ mem.membresia ? `${mem.membresia.nombre} - ${mem.membresia.entidad.abreviacion}` : '—' }}
                                            </p>
                                            <div class="mt-1 flex flex-wrap items-center justify-between gap-2 text-xs">
                                                <span class="font-semibold text-green-700">{{ formatPrecio(mem.precio) }}</span>
                                                <span class="text-gray-500">{{ mem.moneda?.nombre || '—' }}</span>
                                                <span class="text-gray-500">{{ mem.boton_pago?.nombre || '—' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <Link
                                        v-if="$page.props.user.permissions.includes('update esquema_descuentos')"
                                        :href="route('esquemadescuentos.edit', parseInt(esquema.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar esquema"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </Link>
                                    <button
                                        v-if="$page.props.user.permissions.includes('delete esquema_descuentos')"
                                        @click="deleteEsquemaDescuento(parseInt(esquema.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar esquema"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="esquemasOrdenados.length > 0" class="sm:hidden mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>

                    <!-- Tabla desktop -->
                    <div class="mt-4 hidden sm:block">
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
