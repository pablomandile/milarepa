<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps({
    devoluciones: {
        type: Array,
        default: () => [],
    },
});

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
    <Head title="Devoluciones Anexos" />

    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Devoluciones Anexos</h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-6xl mx-auto">
                    <div class="flex justify-between flex-wrap gap-2">
                        <div class="flex gap-2">
                            <Link :href="route('inventario-libros.devoluciones-anexos.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                                NUEVA DEVOLUCION
                            </Link>
                            <Link :href="route('inventario-libros.index')" class="text-gray-800 bg-slate-200 hover:bg-slate-300 py-2 px-4 rounded">
                                VOLVER
                            </Link>
                        </div>
                    </div>

                    <div class="mt-4">
                        <DataTable
                            :value="props.devoluciones"
                            stripedRows
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[10, 25, 50]"
                            tableStyle="min-width: 60rem"
                        >
                            <Column header="Fecha">
                                <template #body="{ data }">
                                    {{ formatDate(data.fecha) }}
                                </template>
                            </Column>
                            <Column header="Devolvedor">
                                <template #body="{ data }">
                                    {{ data.devolvedor?.nombre ?? '-' }}
                                </template>
                            </Column>
                            <Column header="Prestador">
                                <template #body="{ data }">
                                    {{ data.prestador?.nombre ?? '-' }}
                                </template>
                            </Column>
                            <Column header="Libro">
                                <template #body="{ data }">
                                    {{ data.libro?.titulo ?? '-' }}
                                </template>
                            </Column>
                            <Column field="cantidad" header="Cantidad" />
                            <Column header="Usuario">
                                <template #body="{ data }">
                                    {{ data.usuario?.name ?? '-' }}
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
