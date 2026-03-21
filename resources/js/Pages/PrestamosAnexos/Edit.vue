<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrestamoLibroForm from '@/Components/Formularios/PrestamoLibroForm.vue';

const props = defineProps({
    prestamo: {
        type: Object,
        required: true,
    },
    entidades: {
        type: Array,
        default: () => [],
    },
    libros: {
        type: Array,
        default: () => [],
    },
    entidadPrincipal: {
        type: Object,
        default: null,
    },
    authUser: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head title="Editar Prestamo" />

    <AppLayout title="Editar Prestamo">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Prestamo</h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <PrestamoLibroForm
                        :prestamo="props.prestamo"
                        :entidades="props.entidades"
                        :entidad-principal="props.entidadPrincipal"
                        :libros="props.libros"
                        :auth-user="props.authUser"
                        mode="edit"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
