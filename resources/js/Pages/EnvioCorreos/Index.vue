<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';

const props = defineProps({
    envios: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    fechasDisponibles: {
        type: Array,
        required: true,
    },
    tipos: {
        type: Array,
        required: true,
    },
});

const formFilters = reactive({
    destinatario: props.filters.destinatario || '',
    motivo: props.filters.motivo || '',
    fecha: props.filters.fecha || '',
    tipo: props.filters.tipo || '',
});

const applyFilters = () => {
    router.get(route('envio-correos.index'), {
        destinatario: formFilters.destinatario || undefined,
        motivo: formFilters.motivo || undefined,
        fecha: formFilters.fecha || undefined,
        tipo: formFilters.tipo || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const clearFilters = () => {
    formFilters.destinatario = '';
    formFilters.motivo = '';
    formFilters.fecha = '';
    formFilters.tipo = '';
    applyFilters();
};

const formatFecha = (fecha) => {
    if (!fecha) {
        return '-';
    }

    const value = String(fecha).trim();
    const soloFecha = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);

    if (soloFecha) {
        const [, year, month, day] = soloFecha;
        return `${day}/${month}/${year}`;
    }

    const normalizedValue = value.includes(' ') ? value.replace(' ', 'T') : value;
    const date = new Date(normalizedValue);

    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return date.toLocaleDateString('es-UY');
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <AppLayout title="Histórico de Envíos">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Histórico de Envíos</h1>
        </template>

        <div class="py-12">
            <div class="max-w-[110rem] mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Destinatario</label>
                            <InputText
                                v-model="formFilters.destinatario"
                                class="w-full border-gray-300 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                placeholder="Buscar destinatario"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo</label>
                            <InputText
                                v-model="formFilters.motivo"
                                class="w-full border-gray-300 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                placeholder="Buscar motivo"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha</label>
                            <select
                                v-model="formFilters.fecha"
                                class="w-full border-gray-300 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option value="">Todas</option>
                                <option v-for="fecha in fechasDisponibles" :key="fecha" :value="fecha">
                                    {{ formatFecha(fecha) }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
                            <select
                                v-model="formFilters.tipo"
                                class="w-full border-gray-300 dark:border-gray-600 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option value="">Todos</option>
                                <option v-for="tipo in tipos" :key="tipo" :value="tipo">
                                    {{ tipo }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mb-4">
                        <button
                            type="button"
                            @click="clearFilters"
                            class="text-gray-700 dark:text-gray-100 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 py-2 px-4 rounded"
                        >
                            Limpiar
                        </button>
                        <button
                            type="button"
                            @click="applyFilters"
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"
                        >
                            Filtrar
                        </button>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="envios.length > 0" class="space-y-3 sm:hidden">
                        <div
                            v-for="envio in envios"
                            :key="envio.id ?? `${envio.fecha}-${envio.destinatario}`"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm rounded"
                        >
                            <div class="space-y-2 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-paper-plane text-lg text-indigo-600"></i>
                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ formatFecha(envio.fecha) }}</span>
                                    </div>
                                    <span v-if="envio.tipo" class="inline-flex items-center rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 text-xs font-medium flex-shrink-0">
                                        {{ envio.tipo }}
                                    </span>
                                </div>

                                <div class="space-y-1.5 pt-1">
                                    <div class="flex items-start justify-between gap-3 text-sm">
                                        <span class="text-gray-500 flex-shrink-0">Destinatario</span>
                                        <span class="text-right break-all">{{ envio.destinatario || '-' }}</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-3 text-sm">
                                        <span class="text-gray-500 flex-shrink-0">Motivo</span>
                                        <span class="text-right">{{ envio.motivo || '-' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Usuario</span>
                                        <span class="text-right">{{ envio.user?.name || 'Sistema' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="sm:hidden text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay envíos registrados con los filtros actuales.
                    </div>

                    <!-- Tabla desktop -->
                    <DataTable
                        :value="envios"
                        stripedRows
                        paginator
                        :rows="10"
                        :rowsPerPageOptions="[10, 20, 50]"
                        tableStyle="min-width: 65rem"
                        class="hidden sm:block"
                    >
                        <Column field="fecha" header="Fecha">
                            <template #body="slotProps">
                                {{ formatFecha(slotProps.data.fecha) }}
                            </template>
                        </Column>
                        <Column field="tipo" header="Tipo" />
                        <Column field="destinatario" header="Destinatario" />
                        <Column field="motivo" header="Motivo" />
                        <Column header="Usuario">
                            <template #body="slotProps">
                                {{ slotProps.data.user?.name || 'Sistema' }}
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
