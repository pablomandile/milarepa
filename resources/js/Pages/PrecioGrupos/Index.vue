<script>
    export default {
        name: 'PrecioGruposIndex'
    }
</script>

<script setup>
    import { computed, ref } from 'vue';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
    import Swal from 'sweetalert2';
    import DataTable from 'primevue/datatable';
    import Column from 'primevue/column';
    import InputText from 'primevue/inputtext';
    import IconField from 'primevue/iconfield';
    import InputIcon from 'primevue/inputicon';
    import { FilterMatchMode } from 'primevue/api';

    const props = defineProps({
        precioGrupos: {
            type: Array,
            required: true
        }
    });

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

    const gruposFiltradosMobile = computed(() => {
        const term = (filters.value.global.value || '').toString().trim().toLowerCase();
        if (!term) return props.precioGrupos;
        return props.precioGrupos.filter((g) => String(g.nombre ?? '').toLowerCase().includes(term));
    });

    const formatArs = (valor) => {
        if (valor === null || valor === undefined) return '$ 0,00';
        return new Intl.NumberFormat('es-AR', {
            style: 'currency',
            currency: 'ARS',
            minimumFractionDigits: 2
        }).format(valor);
    };

    const formatFecha = (fecha) => {
        if (!fecha) return '—';
        const d = new Date(fecha);
        if (Number.isNaN(d.getTime())) return fecha;
        return d.toLocaleDateString('es-AR', { year: 'numeric', month: '2-digit', day: '2-digit' });
    };

    const deletePrecioGrupo = (id) => {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Se eliminará el grupo de precios y todas sus líneas. No afecta los valores actuales de las membresías.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('precio-grupos.destroy', id), {
                    onSuccess: () => {
                        Swal.fire('¡Eliminado!', 'El grupo de precios fue eliminado.', 'success');
                    },
                    onError: () => {
                        Swal.fire('Error', 'No se pudo eliminar el grupo.', 'error');
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
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Precios de Membresías</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-6xl mx-auto">
                    <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create precio-grupos')">
                        <Link :href="route('precio-grupos.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            NUEVO GRUPO DE PRECIOS
                        </Link>
                    </div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Los grupos guardan un set histórico de precios por membresía. Para aplicarlos, andá a <strong>Membresías</strong> y usá el botón "Tomar precios de".
                    </div>
                    <!-- Buscador móvil -->
                    <div v-if="precioGrupos.length > 0" class="sm:hidden mt-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="gruposFiltradosMobile.length > 0" class="space-y-4 sm:hidden mt-4">
                        <div
                            v-for="grupo in gruposFiltradosMobile"
                            :key="grupo.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-dollar-sign text-2xl text-indigo-600 mt-1"></i>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-base font-semibold text-gray-800 dark:text-gray-100 break-words">{{ grupo.nombre }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Desde: {{ formatFecha(grupo.fecha_desde) }}</p>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-md border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    @click="toggleCardExpanded(grupo.id)"
                                >
                                    <span>{{ isCardExpanded(grupo.id) ? 'Ocultar líneas' : 'Ver líneas' }} ({{ (grupo.lineas || []).length }})</span>
                                    <i class="pi" :class="isCardExpanded(grupo.id) ? 'pi-chevron-up' : 'pi-chevron-down'"></i>
                                </button>

                                <div v-if="isCardExpanded(grupo.id)" class="rounded-md border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 p-3">
                                    <div v-if="(grupo.lineas || []).length === 0" class="text-sm text-gray-500">Sin líneas cargadas.</div>
                                    <div v-else class="space-y-2">
                                        <div v-for="linea in grupo.lineas" :key="linea.id" class="flex items-center justify-between gap-2 text-sm border-b border-gray-200 dark:border-gray-700 last:border-b-0 pb-2 last:pb-0">
                                            <p class="text-gray-800 dark:text-gray-100 break-words flex-1 min-w-0">
                                                {{ linea.membresia ? `${linea.membresia.nombre} - ${linea.membresia.entidad?.abreviacion ?? ''}` : '—' }}
                                            </p>
                                            <span class="font-semibold text-green-700 flex-shrink-0">{{ formatArs(linea.valor) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <Link
                                        v-if="$page.props.user.permissions.includes('update precio-grupos')"
                                        :href="route('precio-grupos.edit', parseInt(grupo.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar grupo"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </Link>
                                    <button
                                        v-if="$page.props.user.permissions.includes('delete precio-grupos')"
                                        @click="deletePrecioGrupo(parseInt(grupo.id))"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar grupo"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="precioGrupos.length > 0" class="sm:hidden mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>

                    <!-- Tabla desktop -->
                    <div class="mt-4 hidden sm:block">
                        <DataTable
                            :value="precioGrupos"
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
                            <Column field="nombre" header="Nombre"></Column>
                            <Column field="fecha_desde" header="Fecha desde">
                                <template #body="slotProps">
                                    {{ formatFecha(slotProps.data.fecha_desde) }}
                                </template>
                            </Column>
                            <Column header="Acciones" class="flex justify-center space-x-2">
                                <template #body="slotProps">
                                    <div class="flex justify-center items-center space-x-4">
                                        <Link
                                            :href="route('precio-grupos.edit', parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('update precio-grupos')"
                                            v-tooltip="'Editar grupo'"
                                            style="display: flex; align-items: center;">
                                            <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1; color: rgb(99, 102, 241);"></i>
                                        </Link>
                                        <button
                                            @click="deletePrecioGrupo(parseInt(slotProps.data.id))"
                                            v-if="$page.props.user.permissions.includes('delete precio-grupos')"
                                            v-tooltip="'Borrar grupo'"
                                            style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1; color: rgb(239, 68, 68);"></i>
                                        </button>
                                    </div>
                                </template>
                            </Column>

                            <template #expansion="{ data }">
                                <DataTable
                                    :value="data.lineas"
                                    class="mt-3"
                                    stripedRows
                                    tableStyle="min-width: 30rem"
                                >
                                    <Column header="Membresía - Entidad">
                                        <template #body="{ data: linea }">
                                            {{ linea.membresia
                                                ? linea.membresia.nombre + ' - ' + (linea.membresia.entidad?.abreviacion ?? '')
                                                : '—'
                                            }}
                                        </template>
                                    </Column>
                                    <Column header="Valor">
                                        <template #body="{ data: linea }">
                                            {{ formatArs(linea.valor) }}
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
