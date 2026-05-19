<script setup>

import { useForm } from '@inertiajs/vue3';
import EsquemaPrecioForm from '@/Components/Formularios/EsquemaPrecioForm.vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';


const form = useForm({
    nombre: ''
});

const submit = () => {
    form.post(route('esquemaprecios.store'));
};

</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight" >Agregar Nuevo Esquema de precios</h1>
        </template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('esquemaprecios.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <EsquemaPrecioForm 
                                :updating="false"
                                :form="form" 
                                @submit="submit"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
