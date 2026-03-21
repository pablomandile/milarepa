<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DevolucionLibroForm from '@/Components/Formularios/DevolucionLibroForm.vue';

const props = defineProps({
    entidades: {
        type: Array,
        default: () => [],
    },
    entidadPrincipal: {
        type: Object,
        default: null,
    },
    libros: {
        type: Array,
        default: () => [],
    },
    authUser: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head title="Nueva Devolución" />

    <AppLayout title="Nueva Devolución">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nueva Devolución</h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <DevolucionLibroForm
                        :entidades="props.entidades"
                        :entidad-principal="props.entidadPrincipal"
                        :libros="props.libros"
                        :auth-user="props.authUser"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
