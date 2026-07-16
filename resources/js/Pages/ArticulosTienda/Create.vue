<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ArticuloTiendaForm from '@/Components/Formularios/ArticuloTiendaForm.vue';

const props = defineProps({
    categorias: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Crear Artículo de Tienda" />

    <AppLayout title="Crear Artículo de Tienda">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Crear Artículo de Tienda</h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <ArticuloTiendaForm :categorias="props.categorias" mode="create" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
