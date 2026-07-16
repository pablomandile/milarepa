<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { FilterMatchMode } from 'primevue/api';
import { computed, ref } from 'vue';

const props = defineProps({
    oraciones: {
        type: Array,
        default: () => [],
    },
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const oracionesFiltradasMobile = computed(() => {
    const term = (filters.value.global.value || '').toString().trim().toLowerCase();
    if (!term) return props.oraciones;
    return props.oraciones.filter((o) => {
        const campos = [o.titulo, o.tipo];
        return campos.some((v) => String(v ?? '').toLowerCase().includes(term));
    });
});

const eliminar = (id) => {
    if (!window.confirm('Desea eliminar esta oración?')) {
        return;
    }

    router.delete(route('oraciones.destroy', id));
};

const formatPrice = (value) => {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

const portadaUrl = (oracion) => {
    if (oracion?.imagen?.ruta) {
        return `/storage/${oracion.imagen.ruta}`;
    }

    return '/storage/img/actividades/imagen-no-disponible.jpg';
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <Head title="Oraciones" />

    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Oraciones</h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 max-w-6xl mx-auto">
                    <div class="flex justify-between flex-wrap gap-2">
                        <div class="flex gap-2">
                            <Link :href="route('oraciones.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                                NUEVA ORACIÓN
                            </Link>
                        </div>
                    </div>

                    <!-- Buscador móvil -->
                    <div v-if="oraciones.length > 0" class="sm:hidden mt-4">
                        <IconField iconPosition="right" class="w-full">
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Buscar..." class="w-full" />
                        </IconField>
                    </div>

                    <!-- Tarjetas móvil -->
                    <div v-if="oracionesFiltradasMobile.length > 0" class="space-y-4 sm:hidden mt-4">
                        <div
                            v-for="oracion in oracionesFiltradasMobile"
                            :key="oracion.id"
                            class="overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm"
                        >
                            <div class="space-y-3 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="h-20 w-14 rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-center overflow-hidden flex-shrink-0">
                                        <img
                                            :src="portadaUrl(oracion)"
                                            :alt="`Portada de ${oracion.titulo || 'oración'}`"
                                            class="max-h-full max-w-full object-contain"
                                        />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-base font-semibold text-gray-800 dark:text-gray-100 break-words">{{ oracion.titulo || '-' }}</p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Tipo</span>
                                        <span class="text-right">{{ oracion.tipo || '-' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="text-gray-500">Precio</span>
                                        <span class="text-right font-semibold">{{ formatPrice(oracion.precio) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <Link
                                        :href="route('oraciones.edit', oracion.id)"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-indigo-500 text-white px-3 text-xs font-semibold hover:bg-indigo-600 transition"
                                        title="Editar oración"
                                    >
                                        <i class="fas fa-pen-to-square"></i>
                                        <span>Editar</span>
                                    </Link>
                                    <button
                                        @click="eliminar(oracion.id)"
                                        class="inline-flex items-center justify-center gap-2 h-9 rounded-full bg-red-500 text-white px-3 text-xs font-semibold hover:bg-red-600 transition"
                                        title="Borrar oración"
                                    >
                                        <i class="fas fa-trash"></i>
                                        <span>Borrar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="oraciones.length > 0" class="sm:hidden mt-4 text-center py-8 text-gray-500 dark:text-gray-400">
                        No hay resultados con los filtros actuales
                    </div>

                    <!-- Tabla desktop -->
                    <div class="mt-4 hidden sm:block">
                        <DataTable
                            :value="oraciones"
                            v-model:filters="filters"
                            :globalFilterFields="['titulo', 'tipo']"
                            stripedRows
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[10, 25, 50]"
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
                        <Column header="Portada" style="width: 110px">
                            <template #body="{ data }">
                                <div class="h-16 w-12 rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-center overflow-hidden">
                                    <img
                                        :src="portadaUrl(data)"
                                        :alt="`Portada de ${data.titulo || 'oración'}`"
                                        class="max-h-full max-w-full object-contain"
                                    />
                                </div>
                            </template>
                        </Column>
                        <Column field="titulo" header="Titulo" />
                        <Column field="tipo" header="Tipo" />
                        <Column header="Precio">
                            <template #body="{ data }">
                                {{ formatPrice(data.precio) }}
                            </template>
                        </Column>
                        <Column header="Acciones" style="width: 180px">
                            <template #body="{ data }">
                                <div class="flex justify-center items-center space-x-4">
                                    <Link
                                        :href="route('oraciones.edit', data.id)"
                                        v-tooltip="'Editar oración'"
                                        class="text-indigo-600 hover:text-indigo-800"
                                        style="display: flex; align-items: center;"
                                    >
                                        <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1;"></i>
                                    </Link>
                                    <button
                                        @click="eliminar(data.id)"
                                        v-tooltip="'Borrar oración'"
                                        class="text-red-600 hover:text-red-800"
                                        style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;"
                                    >
                                        <i class="fas fa-trash" style="font-size: 18px !important; line-height: 1;"></i>
                                    </button>
                                </div>
                            </template>
                        </Column>
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
