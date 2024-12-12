<script>
    export default {
        name: 'CoordinadoresCreate'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue'
    import CoordinadorForm from '@/Components/Formularios/CoordinadorForm.vue'

    const form = useForm({
        nombre: '',
        telefono: '',
        email: ''
    })
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Agregar Nuevo Coordinador</h1>
        </template>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <CoordinadorForm 
                            :updating="false"
                            :form="form" @submit="form.post(route('coordinadores.store'))"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>