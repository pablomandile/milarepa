<script setup>
import { useForm } from '@inertiajs/vue3';
import EsquemaPrecioForm from '@/Components/Formularios/EsquemaPrecioForm.vue';
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';


const props = defineProps({
    membresias: Array,
    monedas: Array,
    form: Object
});

const form = useForm({
    nombre: '',
    membresias: [
        { membresia_id: null, precio: null, moneda_id: null },
    ],
});

const submit = () => {
    form.post(route('esquemaprecios.store'));
};

// Manejar la actualización del formulario desde el componente hijo
const updateForm = (updatedForm) => {
    form.fill(updatedForm);
};

</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Agregar Nuevo Esquema de precios</h1>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('membresias.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <EsquemaPrecioForm 
                            :updating="false"
                            :membresias="membresias" 
                            :monedas="monedas"
                            :form="form" @submit="form.post(route('membresias.store'))"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
