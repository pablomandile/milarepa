<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import LibroForm from '@/Components/Formularios/LibroForm.vue';

const props = defineProps({
    imagenes: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Crear Libro" />

    <AppLayout title="Crear Libro">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Crear Libro</h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <LibroForm :imagenes="props.imagenes" mode="create" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
