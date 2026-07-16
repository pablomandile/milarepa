<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InventarioOracionForm from '@/Components/Formularios/InventarioOracionForm.vue';

const props = defineProps({
    oraciones: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Crear Inventario de Oraciones" />

    <AppLayout title="Crear Inventario de Oraciones">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Crear Inventario de Oraciones</h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <InventarioOracionForm :oraciones="props.oraciones" mode="create" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
