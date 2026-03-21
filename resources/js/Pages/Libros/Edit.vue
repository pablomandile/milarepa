<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import LibroForm from '@/Components/Formularios/LibroForm.vue';

const props = defineProps({
    libro: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Editar Libro" />

    <AppLayout title="Editar Libro">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Libro</h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <LibroForm :libro="props.libro" mode="edit" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
