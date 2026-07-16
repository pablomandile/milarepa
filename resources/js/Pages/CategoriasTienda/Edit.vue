<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CategoriaTiendaForm from '@/Components/Formularios/CategoriaTiendaForm.vue';

const props = defineProps({
    categoria: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Editar Categoría de Tienda" />

    <AppLayout title="Editar Categoría de Tienda">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Editar Categoría de Tienda</h2>
        </template>

        <div class="py-8">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <CategoriaTiendaForm :categoria="props.categoria" mode="edit" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
