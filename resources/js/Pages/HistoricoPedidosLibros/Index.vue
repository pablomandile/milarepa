<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps({
    historicoPedidos: {
        type: Array,
        default: () => [],
    },
});

const formatMoney = (value) => {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

const formatDate = (value) => {
    if (!value) return '-';
    return new Date(value).toLocaleString('es-AR');
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <Head title="Histórico de pedidos" />

    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Histórico de pedidos</h1>
        </template>

        <div class="py-12">
            <div class="max-w-[110rem] mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-[108rem] mx-auto">
                    <div class="flex justify-between flex-wrap gap-2 mb-4">
                        <Link :href="route('inventario-libros.index')" class="text-gray-800 bg-slate-200 hover:bg-slate-300 py-2 px-4 rounded">
                            VOLVER
                        </Link>
                    </div>

                    <DataTable
                        :value="props.historicoPedidos"
                        stripedRows
                        paginator
                        :rows="10"
                        :rowsPerPageOptions="[10, 25, 50]"
                        tableStyle="min-width: 70rem"
                    >
                        <Column header="Fecha">
                            <template #body="{ data }">
                                {{ formatDate(data.fecha || data.created_at) }}
                            </template>
                        </Column>

                        <Column header="Libro">
                            <template #body="{ data }">
                                {{ data.libro?.titulo || '-' }}
                            </template>
                        </Column>

                        <Column field="cantidad_total" header="Cant. total" />
                        <Column field="cantidad_inicial" header="Cant. inicial entidad" />
                        <Column field="cantidad_vendida" header="Cant. vendida" />
                        <Column field="cantidad_final" header="Cant. final entidad" />

                        <Column header="Importe">
                            <template #body="{ data }">
                                {{ formatMoney(data.importe) }}
                            </template>
                        </Column>

                        <Column header="Vendedor">
                            <template #body="{ data }">
                                {{ data.vendedor?.name || '-' }}
                            </template>
                        </Column>

                        <Column header="Email comprador">
                            <template #body="{ data }">
                                {{ data.email_comprador || '-' }}
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
