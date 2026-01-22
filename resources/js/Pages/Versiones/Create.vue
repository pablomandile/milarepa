<script>
export default {
    name: 'VersionesCreate'
}
</script>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import VersionForm from '@/Components/Formularios/VersionForm.vue';
import { Link } from '@inertiajs/vue3';

const form = useForm({
    version: ''
})
</script>

<template>
    <AppLayout title="Crear Nueva Versión">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Agregar Nueva Versión</h1>
        </template>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('versiones.index')" 
                            class="inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-600 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Volver
                        </Link>
                    </div>
                    <div class="p-6 bg-white border-b border-gray-200">
                        <VersionForm 
                            :updating="false"
                            :form="form" 
                            @submit="form.post(route('versiones.store'))" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
