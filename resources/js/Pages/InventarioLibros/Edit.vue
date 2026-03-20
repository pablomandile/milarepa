<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InventarioLibroForm from '@/Components/Formularios/InventarioLibroForm.vue';

const props = defineProps({
    inventario: {
        type: Object,
        required: true,
    },
    libros: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Editar Inventario de Libros" />

    <AppLayout title="Editar Inventario de Libros">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Inventario de Libros</h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <InventarioLibroForm :inventario="props.inventario" :libros="props.libros" mode="edit" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
