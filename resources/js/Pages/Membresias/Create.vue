<script>
    export default {
        name: 'MembresiasCreate'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue'
    import MembresiaForm from '@/Components/Formularios/MembresiaForm.vue'
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        entidades: {
        type: Array,
        default: () => []
        }
    });

    const form = useForm({
        nombre: '',
        descripcion: '',
        entidad_id: ''
    })
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Agregar Nueva Membresia</h1>
        </template>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('membresias.gestion')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <MembresiaForm 
                            :updating="false"
                            :entidades="entidades"
                            :form="form" @submit="form.post(route('membresias.store'))"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>