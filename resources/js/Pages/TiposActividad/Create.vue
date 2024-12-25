<script>
    export default {
        name: 'TiposActividadCreate'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import TipoActividadForm from '@/Components/Formularios/TipoActividadForm.vue';
    import { Link } from '@inertiajs/vue3';


    const form = useForm({
        nombre: ''
    })
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Agregar Nuevo Tipo de Actividad</h1>
        </template>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('tiposactividad.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <TipoActividadForm 
                            :updating="false"
                            :form="form" @submit="form.post(route('tiposactividad.store'))"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>