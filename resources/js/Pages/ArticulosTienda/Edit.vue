<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ArticuloTiendaForm from '@/Components/Formularios/ArticuloTiendaForm.vue';

const props = defineProps({
    articulo: {
        type: Object,
        required: true,
    },
    categorias: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Editar Artículo de Tienda" />

    <AppLayout title="Editar Artículo de Tienda">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Editar Artículo de Tienda</h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <ArticuloTiendaForm :articulo="props.articulo" :categorias="props.categorias" mode="edit" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
