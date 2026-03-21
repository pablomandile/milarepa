<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps({
    libros: {
        type: Array,
        default: () => [],
    },
});

const eliminar = (id) => {
    if (!window.confirm('Desea eliminar este libro?')) {
        return;
    }

    router.delete(route('libros.destroy', id));
};

const formatPrice = (value) => {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
};

const portadaUrl = (libro) => {
    if (libro?.imagen?.ruta) {
        return `/storage/${libro.imagen.ruta}`;
    }

    return '/storage/img/actividades/imagen-no-disponible.jpg';
};
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <Head title="Libros" />

    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Libros</h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-6xl mx-auto">
                    <div class="flex justify-between flex-wrap gap-2">
                        <div class="flex gap-2">
                            <Link :href="route('libros.create')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                                NUEVO LIBRO
                            </Link>
                            <Link :href="route('inventario-libros.ventas.index')" class="text-white bg-emerald-600 hover:bg-emerald-700 py-2 px-4 rounded">
                                VENTA
                            </Link>
                        </div>
                    </div>

                    <div class="mt-4">
                        <DataTable
                            :value="libros"
                            stripedRows
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[10, 25, 50]"
                            tableStyle="min-width: 50rem"
                        >
                        <Column header="Portada" style="width: 110px">
                            <template #body="{ data }">
                                <div class="h-16 w-12 rounded border border-gray-200 bg-white flex items-center justify-center overflow-hidden">
                                    <img
                                        :src="portadaUrl(data)"
                                        :alt="`Portada de ${data.titulo || 'libro'}`"
                                        class="max-h-full max-w-full object-contain"
                                    />
                                </div>
                            </template>
                        </Column>
                        <Column field="titulo" header="Titulo" />
                        <Column field="autor" header="Autor" />
                        <Column field="editorial" header="Editorial" />
                        <Column field="isbn" header="ISBN" />
                        <Column header="Precio">
                            <template #body="{ data }">
                                {{ formatPrice(data.precio) }}
                            </template>
                        </Column>
                        <Column header="Acciones" style="width: 180px">
                            <template #body="{ data }">
                                <div class="flex justify-center items-center space-x-4">
                                    <Link
                                        :href="route('libros.edit', data.id)"
                                        v-tooltip="'Editar libro'"
                                        class="text-indigo-600 hover:text-indigo-800"
                                        style="display: flex; align-items: center;"
                                    >
                                        <i class="fas fa-pen-to-square" style="font-size: 18px !important; line-height: 1;"></i>
                                    </Link>
                                    <button
                                        @click="eliminar(data.id)"
                                        v-tooltip="'Borrar libro'"
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
