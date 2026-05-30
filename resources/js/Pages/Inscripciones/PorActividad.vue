<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { FilterMatchMode } from 'primevue/api';
import { computed, ref } from 'vue';

const props = defineProps({
    actividades: {
        type: Array,
        default: () => [],
    },
    resumen: {
        type: Object,
        default: () => ({}),
    },
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const actividadesFiltradasMobile = computed(() => {
    const term = (filters.value.global.value || '').toString().trim().toLowerCase();
    if (!term) return props.actividades;
    return props.actividades.filter((a) => {
        const campos = [a.nombre, a.maestro, a.fecha_formateada];
        return campos.some((v) => String(v ?? '').toLowerCase().includes(term));
    });
});

const diasRestantesClass = (dias) => {
    if (dias === null || dias === undefined || dias < 0) return 'bg-gray-100 text-gray-600';
    if (dias === 0) return 'bg-red-100 text-red-700';
    if (dias <= 3) return 'bg-amber-100 text-amber-700';
    return 'bg-emerald-100 text-emerald-700';
};

const formatMoney = (value) => {
    const numeric = Number(value);
    if (!Number.isFinite(numeric)) return '0.00';
    return numeric.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDiasRestantes = (value) => {
    if (value === null || value === undefined) return '-';
    if (value === 0) return 'Hoy';
    if (value < 0) return '-';
    return `${value} dia(s)`;
};

const formatPercent = (value) => {
    const numeric = Number(value);
    if (!Number.isFinite(numeric)) return '0.0%';
    return `${numeric.toLocaleString('es-AR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}%`;
};
</script>

<template>
    <AppLayout title="Inscripciones por Actividad">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Inscripciones por Actividad</h1>
        </template>

        <div class="py-12">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="mb-6 flex justify-center">
                            <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:w-auto xl:grid-cols-6">
                            <div class="rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 p-4 text-center text-white shadow-sm">
                                <p class="text-md font-medium leading-tight text-indigo-100">Eventos activos</p>
                                <p class="mt-1 text-2xl font-bold">{{ props.resumen.eventos_activos || 0 }}</p>
                            </div>

                            <div class="rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 p-4 text-center text-white shadow-sm">
                                <p class="text-md font-medium leading-tight text-emerald-100">Total de inscriptos</p>
                                <p class="mt-1 text-2xl font-bold">{{ props.resumen.total_inscriptos || 0 }}</p>
                            </div>

                            <div class="rounded-xl bg-gradient-to-br from-sky-500 to-sky-700 p-4 text-center text-white shadow-sm">
                                <p class="text-xs font-medium leading-tight text-sky-100">Inscriptos con TK</p>
                                <p class="mt-1 text-2xl font-bold">{{ props.resumen.inscriptos_con_tk || 0 }}</p>
                                <p class="mt-1 text-xs text-sky-100">{{ formatPercent(props.resumen.inscriptos_con_tk_pct) }} del total</p>
                            </div>

                            <div class="rounded-xl bg-gradient-to-br from-violet-500 to-violet-700 p-4 text-center text-white shadow-sm">
                                <p class="text-xs font-medium leading-tight text-violet-100">Inscriptos no TK</p>
                                <p class="mt-1 text-2xl font-bold">{{ props.resumen.inscriptos_sin_tk || 0 }}</p>
                                <p class="mt-1 text-xs text-violet-100">{{ formatPercent(props.resumen.inscriptos_sin_tk_pct) }} del total</p>
                            </div>

                            <div class="w-full rounded-xl bg-gradient-to-br from-amber-500 to-amber-700 p-4 text-center text-white shadow-sm xl:max-w-[170px] xl:justify-self-center">
                                <p class="text-xs font-medium leading-tight text-amber-100">Inscriptos en ultimos 5 dias</p>
                                <p class="mt-1 text-2xl font-bold">{{ props.resumen.inscriptos_ultimos_5_dias || 0 }}</p>
                            </div>

                            <div class="rounded-xl bg-gradient-to-br from-rose-500 to-rose-700 p-4 text-center text-white shadow-sm">
                                <p class="text-xs font-medium leading-tight text-rose-100">Pendientes de pago</p>
                                <p class="mt-1 text-2xl font-bold">{{ props.resumen.pendientes_pago || 0 }}</p>
                            </div>
                            </div>
                        </div>

                        <!-- Buscador móvil -->
                        <div v-if="props.actividades.length > 0" class="sm:hidden mb-4">
                            <IconField iconPosition="right" class="w-full">
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                            </IconField>
                        </div>

                        <!-- Tarjetas móvil -->
                        <div v-if="actividadesFiltradasMobile.length > 0" class="space-y-4 sm:hidden">
                            <div
                                v-for="actividad in actividadesFiltradasMobile"
                                :key="actividad.id"
                                class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                            >
                                <div class="space-y-3 p-4">
                                    <div class="flex items-start gap-3">
                                        <i class="fas fa-calendar-check text-2xl text-indigo-600 mt-1"></i>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-base font-semibold text-gray-800 dark:text-gray-100 break-words">{{ actividad.nombre }}</p>
                                            <p v-if="actividad.maestro" class="text-sm text-gray-600 dark:text-gray-400">
                                                <i class="fas fa-user-tie mr-1"></i>{{ actividad.maestro }}
                                            </p>
                                            <p v-if="actividad.fecha_formateada" class="text-sm text-gray-600 dark:text-gray-400">
                                                <i class="fas fa-calendar mr-1"></i>{{ actividad.fecha_formateada }}
                                            </p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold flex-shrink-0" :class="diasRestantesClass(actividad.dias_restantes)">
                                            {{ formatDiasRestantes(actividad.dias_restantes) }}
                                        </span>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between gap-3 text-sm">
                                            <span class="text-gray-500">Total inscriptos</span>
                                            <span class="text-right font-semibold">{{ actividad.total_inscriptos || 0 }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3 text-sm">
                                            <span class="text-gray-500">Últimos 5 días</span>
                                            <span class="text-right">{{ actividad.inscriptos_ultimos_5_dias || 0 }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3 text-sm">
                                            <span class="text-gray-500">Pendientes pago</span>
                                            <span class="text-right">{{ actividad.pendientes_pago || 0 }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3 text-sm">
                                            <span class="text-gray-500">Pendiente (importe)</span>
                                            <span class="text-right font-semibold text-rose-700">${{ formatMoney(actividad.pendiente_importe) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="props.actividades.length > 0" class="sm:hidden text-center py-8 text-gray-500 dark:text-gray-400">
                            No hay resultados con los filtros actuales
                        </div>
                        <div v-else-if="props.actividades.length === 0" class="sm:hidden text-center py-8">
                            <p class="text-gray-500 text-lg">No hay actividades activas con inscripciones.</p>
                        </div>

                        <!-- Tabla desktop -->
                        <DataTable
                            :value="props.actividades"
                            v-model:filters="filters"
                            :globalFilterFields="['nombre', 'maestro', 'fecha_formateada']"
                            dataKey="id"
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[10, 20, 50]"
                            responsiveLayout="scroll"
                            class="p-datatable-sm hidden sm:block"
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
                            <Column class="font-semibold" field="nombre" header="Nombre" sortable />
                            <Column field="maestro" header="Maestr@" sortable />
                            <Column field="fecha_formateada" header="Fecha" sortable />
                            <Column header="Dias restantes" sortable sortField="dias_restantes" headerClass="text-center" bodyClass="text-center">
                                <template #body="{ data }">
                                    {{ formatDiasRestantes(data.dias_restantes) }}
                                </template>
                            </Column>
                            <Column field="total_inscriptos" header="Total inscriptos" sortable headerClass="text-center" bodyClass="text-center" />
                            <Column field="inscriptos_ultimos_5_dias" header="Inscriptos ultimos 5 dias" sortable headerClass="text-center" bodyClass="text-center" />
                            <Column field="pendientes_pago" header="Pendientes de pago (cantidad)" sortable headerClass="text-center" bodyClass="text-center" />
                            <Column header="Pendiente (importe)" sortable sortField="pendiente_importe" headerClass="text-center" bodyClass="text-center">
                                <template #body="{ data }">
                                    <span class="font-semibold">${{ formatMoney(data.pendiente_importe) }}</span>
                                </template>
                            </Column>

                            <template #empty>
                                <div class="text-center py-8">
                                    <p class="text-gray-500 text-lg">No hay actividades activas con inscripciones.</p>
                                </div>
                            </template>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>
