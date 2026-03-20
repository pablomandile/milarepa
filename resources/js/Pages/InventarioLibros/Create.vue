<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InventarioLibroForm from '@/Components/Formularios/InventarioLibroForm.vue';

const props = defineProps({
    libros: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Crear Inventario de Libros" />

    <AppLayout title="Crear Inventario de Libros">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Crear Inventario de Libros</h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <InventarioLibroForm :libros="props.libros" mode="create" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
