<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InventarioOtroForm from '@/Components/Formularios/InventarioOtroForm.vue';

const props = defineProps({
    inventario: {
        type: Object,
        required: true,
    },
    otros: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Editar Inventario de Otros" />

    <AppLayout title="Editar Inventario de Otros">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Editar Inventario de Otros</h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <InventarioOtroForm :inventario="props.inventario" :otros="props.otros" mode="edit" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
