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

// El total sólo suma cobros confirmados; lo "a revisar" se informa aparte.
const total = computed(() => props.cobros.reduce(
    (acc, c) => acc + (c.estado === 'a_revisar' ? 0 : Number(c.monto || 0)),
    0
));

const cantidadARevisar = computed(() => props.cobros.filter((c) => c.estado === 'a_revisar').length);

const estadoLabel = (estado) => (estado === 'a_revisar' ? 'A revisar' : 'Confirmado');
const estadoClass = (estado) => (estado === 'a_revisar'
    ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'
    : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300');
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
                            {{ cobros.length }} cobros · Total confirmado:
                            <span class="font-semibold text-green-700 dark:text-green-400">{{ formatMoney(total) }}</span>
                            <span v-if="cantidadARevisar" class="ml-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                {{ cantidadARevisar }} a revisar
                            </span>
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
                        :global-filter-fields="['dominio', 'detalle', 'medio', 'referencia', 'origen', 'estado', 'observaciones']"
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
                        <Column field="estado" header="Estado" sortable>
                            <template #body="{ data }">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="estadoClass(data.estado)"
                                >
                                    {{ estadoLabel(data.estado) }}
                                </span>
                            </template>
                        </Column>
                        <Column header="Comprobante">
                            <template #body="{ data }">
                                <template v-if="data.comprobantes?.length">
                                    <a
                                        v-for="(ruta, idx) in data.comprobantes"
                                        :key="idx"
                                        :href="`/storage/${ruta}`"
                                        target="_blank"
                                        rel="noopener"
                                        class="mr-2 text-indigo-600 hover:text-indigo-800"
                                    >
                                        Ver{{ data.comprobantes.length > 1 ? ` ${idx + 1}` : '' }}
                                    </a>
                                </template>
                                <span v-else>-</span>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
