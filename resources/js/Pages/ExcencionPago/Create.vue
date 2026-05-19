<script>
    export default {
        name: 'ExcencionPagoCreate'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import ExcencionPagoForm from '@/Components/Formularios/ExcencionPagoForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        usuarios: {
            type: Array,
            default: () => []
        },
        actividades: {
            type: Array,
            default: () => []
        }
    });

    const form = useForm({
        user_id: null,
        actividad_id: null,
        importe: ''
    })
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight" >Agregar Excención de Pago</h1>
        </template>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('excencionpago.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <ExcencionPagoForm 
                                :updating="false"
                                :form="form"
                                :usuarios="props.usuarios"
                                :actividades="props.actividades"
                                @submit="form.post(route('excencionpago.store'))"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
