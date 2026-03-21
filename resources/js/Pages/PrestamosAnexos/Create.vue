<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrestamoLibroForm from '@/Components/Formularios/PrestamoLibroForm.vue';

const props = defineProps({
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
    <Head title="Nuevo Prestamo" />

    <AppLayout title="Nuevo Prestamo">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo Prestamo</h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                    <PrestamoLibroForm
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
