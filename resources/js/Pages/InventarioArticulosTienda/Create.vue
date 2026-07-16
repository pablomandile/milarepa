<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InventarioArticuloTiendaForm from '@/Components/Formularios/InventarioArticuloTiendaForm.vue';

const props = defineProps({
    articulos: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Crear Inventario de Artículos" />

    <AppLayout title="Crear Inventario de Artículos">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Crear Inventario de Artículos</h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <InventarioArticuloTiendaForm :articulos="props.articulos" mode="create" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
