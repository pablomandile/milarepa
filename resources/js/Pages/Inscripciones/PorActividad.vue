<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

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
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Inscripciones por Actividad</h1>
        </template>

        <div class="py-12">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
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

                        <DataTable
                            :value="props.actividades"
                            dataKey="id"
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[10, 20, 50]"
                            responsiveLayout="scroll"
                            class="p-datatable-sm"
                        >
                            <Column class="font-semibold" field="nombre" header="Nombre" sortable />
                            <Column field="maestro" header="Maestro" sortable />
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
