<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import OracionForm from '@/Components/Formularios/OracionForm.vue';

const props = defineProps({
    oracion: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Editar Oración" />

    <AppLayout title="Editar Oración">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Editar Oración</h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <OracionForm :oracion="props.oracion" mode="edit" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
