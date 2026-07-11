<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import { FilterMatchMode } from 'primevue/api';

const props = defineProps({
    cobros: {
        type: Array,
        default: () => [],
    },
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const formatMoney = (value) => new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    minimumFractionDigits: 2,
}).format(Number(value || 0));

const formatDate = (value) => {
    if (!value) return '-';
    const fecha = new Date(value);
    if (!Number.isNaN(fecha.getTime())) {
        return fecha.toLocaleDateString('es-AR');
    }
    return String(value).split('T')[0];
};

const total = computed(() => props.cobros.reduce((acc, c) => acc + Number(c.monto || 0), 0));
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <Head title="Cobros" />

    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Cobros</h1>
        </template>

        <div class="py-12">
            <div class="max-w-[110rem] mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-[108rem] mx-auto">
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            {{ cobros.length }} cobros · Total:
                            <span class="font-semibold text-green-700 dark:text-green-400">{{ formatMoney(total) }}</span>
                        </div>
                        <InputText
                            v-model="filters['global'].value"
                            placeholder="Buscar (dominio, detalle, medio, referencia…)"
                            class="w-full sm:w-96 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm"
                        />
                    </div>

                    <DataTable
                        :value="cobros"
                        :filters="filters"
                        :global-filter-fields="['dominio', 'detalle', 'medio', 'referencia', 'origen', 'observaciones']"
                        stripedRows
                        paginator
                        :rows="15"
                        :rows-per-page-options="[15, 25, 50, 100]"
                        sortField="fecha"
                        :sortOrder="-1"
                        tableStyle="min-width: 70rem"
                    >
                        <template #empty>
                            <div class="py-6 text-center text-gray-500">No hay cobros registrados.</div>
                        </template>

                        <Column field="fecha" header="Fecha" sortable>
                            <template #body="{ data }">{{ formatDate(data.fecha) }}</template>
                        </Column>
                        <Column field="dominio" header="Dominio" sortable />
                        <Column field="detalle" header="Detalle" sortable>
                            <template #body="{ data }">
                                <div>{{ data.detalle }}</div>
                                <div v-if="data.observaciones" class="text-xs text-gray-400">{{ data.observaciones }}</div>
                            </template>
                        </Column>
                        <Column field="monto" header="Monto" sortable>
                            <template #body="{ data }">{{ formatMoney(data.monto) }}</template>
                        </Column>
                        <Column field="medio" header="Medio">
                            <template #body="{ data }">{{ data.medio ?? '-' }}</template>
                        </Column>
                        <Column field="referencia" header="Referencia">
                            <template #body="{ data }">{{ data.referencia ?? '-' }}</template>
                        </Column>
                        <Column field="origen" header="Origen" sortable />
                        <Column header="Comprobante">
                            <template #body="{ data }">
                                <a
                                    v-if="data.comprobante"
                                    :href="`/storage/${data.comprobante}`"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-indigo-600 hover:text-indigo-800"
                                >
                                    Ver
                                </a>
                                <span v-else>-</span>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
