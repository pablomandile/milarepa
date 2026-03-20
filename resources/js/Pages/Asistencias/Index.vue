<script>
export default {
    name: 'AsistenciasIndex'
}
</script>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

defineProps({
    asistencias: {
        type: Array,
        required: true,
    },
});

const getEstadoClass = (estado) => {
    if (estado === 'Presente') {
        return 'bg-green-100 text-green-700';
    }
    if (estado === 'Ausente') {
        return 'bg-red-100 text-red-700';
    }
    return 'bg-yellow-100 text-yellow-700';
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Asistencias</h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-6xl mx-auto">
                    <DataTable
                        :value="asistencias"
                        stripedRows
                        paginator
                        :rows="10"
                        :rowsPerPageOptions="[10, 20, 50]"
                        tableStyle="min-width: 60rem"
                    >
                        <Column field="id" header="#" style="width: 80px" />

                        <Column field="inscripcion_id" header="Inscripción" />

                        <Column header="Clase">
                            <template #body="slotProps">
                                {{ slotProps.data.inscripcion_clase?.clase?.nombre || '-' }}
                            </template>
                        </Column>

                        <Column header="Usuario">
                            <template #body="slotProps">
                                {{ slotProps.data.usuario?.name || '-' }}
                            </template>
                        </Column>

                        <Column header="Actividad">
                            <template #body="slotProps">
                                {{ slotProps.data.inscripcion?.actividad?.nombre || '-' }}
                            </template>
                        </Column>

                        <Column field="asistencia" header="Asistencia">
                            <template #body="slotProps">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="getEstadoClass(slotProps.data.asistencia)"
                                >
                                    {{ slotProps.data.asistencia }}
                                </span>
                            </template>
                        </Column>

                        <Column header="Fecha">
                            <template #body="slotProps">
                                {{ new Date(slotProps.data.created_at).toLocaleDateString('es-AR') }}
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
