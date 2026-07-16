<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import OtroForm from '@/Components/Formularios/OtroForm.vue';

const props = defineProps({
    otro: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Editar Artículo" />

    <AppLayout title="Editar Artículo">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Editar Artículo</h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <OtroForm :otro="props.otro" mode="edit" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
