<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps({
    inventarioPorEntidad: {
        type: Array,
        default: () => [],
    },
});

const entidadFiltro = ref(null);
const libroFiltro = ref(null);

const entidadesDisponibles = computed(() => {
    const mapa = new Map();

    props.inventarioPorEntidad.forEach((item) => {
        const id = item?.entidad_id;
        const nombre = item?.entidad_nombre;

        if (!id || !nombre) {
            return;
        }

        if (!mapa.has(id)) {
            mapa.set(id, { id, nombre });
        }
    });

    return Array.from(mapa.values()).sort((a, b) => a.nombre.localeCompare(b.nombre));
});

const librosDisponibles = computed(() => {
    const mapa = new Map();

    props.inventarioPorEntidad.forEach((item) => {
        const id = item?.libro_id;
        const titulo = item?.libro_titulo;

        if (!id || !titulo) {
            return;
        }

        if (!mapa.has(id)) {
            mapa.set(id, { id, titulo });
        }
    });

    return Array.from(mapa.values()).sort((a, b) => a.titulo.localeCompare(b.titulo));
});

const inventarioFiltrado = computed(() => {
    return props.inventarioPorEntidad.filter((item) => {
        const coincideEntidad = !entidadFiltro.value || Number(item?.entidad_id) === Number(entidadFiltro.value);
        const coincideLibro = !libroFiltro.value || Number(item?.libro_id) === Number(libroFiltro.value);

        return coincideEntidad && coincideLibro;
    });
});
</script>

<style scoped>
@import '../../../css/datatable-header-style.css';
</style>

<template>
    <Head title="Inventario por Entidad" />

    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Inventario por Entidad</h1>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-6 bg-white border-b border-gray-200 max-w-6xl mx-auto">
                    <div class="flex justify-between flex-wrap gap-2">
                        <Link :href="route('inventario-libros.index')" class="text-gray-800 bg-slate-200 hover:bg-slate-300 py-2 px-4 rounded">
                            VOLVER
                        </Link>
                    </div>

                    <div class="mt-4">
                        <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="entidad_filtro" class="block text-sm font-medium text-gray-700 mb-1">
                                    Filtrar por entidad
                                </label>
                                <select
                                    id="entidad_filtro"
                                    v-model="entidadFiltro"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option :value="null">Todas</option>
                                    <option v-for="entidad in entidadesDisponibles" :key="entidad.id" :value="entidad.id">
                                        {{ entidad.nombre }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label for="libro_filtro" class="block text-sm font-medium text-gray-700 mb-1">
                                    Filtrar por libro
                                </label>
                                <select
                                    id="libro_filtro"
                                    v-model="libroFiltro"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option :value="null">Todos</option>
                                    <option v-for="libro in librosDisponibles" :key="libro.id" :value="libro.id">
                                        {{ libro.titulo }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <DataTable
                            :value="inventarioFiltrado"
                            stripedRows
                            paginator
                            :rows="10"
                            :rowsPerPageOptions="[10, 25, 50]"
                            tableStyle="min-width: 60rem"
                        >
                            <Column field="entidad_nombre" header="Entidad" />
                            <Column field="libro_titulo" header="Libro" />
                            <Column field="cantidad" header="Cantidad" />
                        </DataTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
