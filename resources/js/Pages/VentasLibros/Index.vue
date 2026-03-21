<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import VentaLibroForm from '@/Components/Formularios/VentaLibroForm.vue';

const props = defineProps({
    entidades: {
        type: Array,
        default: () => [],
    },
    ventas: {
        type: Array,
        default: () => [],
    },
    modosPago: {
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

    const fecha = new Date(value);
    if (!Number.isNaN(fecha.getTime())) {
        return fecha.toLocaleDateString('es-AR');
    }

    return String(value).split('T')[0];
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <Head title="Venta de Libros" />

    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Venta de Libros</h1>
        </template>

        <div class="py-12">
            <div class="max-w-[110rem] mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-[108rem] mx-auto">
                    <div class="flex justify-between flex-wrap gap-2 mb-4">
                        <Link :href="route('inventario-libros.index')" class="text-gray-800 bg-slate-200 hover:bg-slate-300 py-2 px-4 rounded">
                            VOLVER
                        </Link>
                    </div>

                    <VentaLibroForm :entidades="entidades" :modos-pago="modosPago" />

                    <div class="mt-8">
                        <h2 class="text-lg font-semibold text-gray-800 mb-3">Ventas realizadas</h2>
                        <DataTable
                            :value="ventas"
                            stripedRows
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[10, 25, 50]"
                            tableStyle="min-width: 70rem"
                        >
                            <Column header="Fecha">
                                <template #body="{ data }">
                                    {{ formatDate(data.fecha) }}
                                </template>
                            </Column>
                            <Column header="Entidad">
                                <template #body="{ data }">
                                    {{ data.entidad?.nombre ?? '-' }}
                                </template>
                            </Column>
                            <Column header="Libro">
                                <template #body="{ data }">
                                    {{ data.libro?.titulo ?? '-' }}
                                </template>
                            </Column>
                            <Column field="precio_unitario" header="Precio unit.">
                                <template #body="{ data }">
                                    {{ formatMoney(data.precio_unitario) }}
                                </template>
                            </Column>
                            <Column field="cantidad" header="Cantidad" />
                            <Column field="montoTotal" header="Monto total">
                                <template #body="{ data }">
                                    {{ formatMoney(data.montoTotal) }}
                                </template>
                            </Column>
                            <Column field="modo" header="Modo" />
                            <Column header="Comprobante">
                                <template #body="{ data }">
                                    <a
                                        v-if="data.comprobante?.ruta"
                                        :href="`/storage/${data.comprobante.ruta}`"
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
        </div>
    </AppLayout>
</template>
