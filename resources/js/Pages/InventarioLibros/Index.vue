<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const props = defineProps({
    inventarios: {
        type: Array,
        default: () => [],
    },
});

const eliminar = (id) => {
    if (!window.confirm('Desea eliminar este registro de inventario?')) {
        return;
    }

    router.delete(route('inventario-libros.destroy', id));
};
</script>

<template>
    <Head title="Inventario Libros" />

    <AppLayout title="Inventario Libros">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Inventario Libros</h2>
                <Link :href="route('inventario-libros.create')">
                    <PrimaryButton>Nuevo registro</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <DataTable
                        :value="inventarios"
                        paginator
                        :rows="10"
                        :rowsPerPageOptions="[10, 25, 50]"
                        responsiveLayout="scroll"
                        class="p-datatable-sm"
                    >
                        <Column field="id" header="#" style="width: 70px" />
                        <Column header="Libro">
                            <template #body="{ data }">
                                {{ data.libro?.titulo ?? 'Sin libro' }}
                            </template>
                        </Column>
                        <Column field="cantidad" header="Cantidad" />
                        <Column header="Acciones" style="width: 220px">
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <Link :href="route('inventario-libros.edit', data.id)">
                                        <button class="px-3 py-1 rounded bg-yellow-500 text-white text-sm">Editar</button>
                                    </Link>
                                    <button
                                        @click="eliminar(data.id)"
                                        class="px-3 py-1 rounded bg-red-600 text-white text-sm"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
