<script>
    export default {
        name: 'LugaresHospedajeEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import LugarHospedajeForm from '@/Components/Formularios/LugarHospedajeForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        lugarhospedaje:{
            type: Object,
            required: true
        }
    });
    // console.log(props.lugarhospedaje);
    if (!props.lugarhospedaje) {
        console.error('El lugar de Hospedaje no está definido');
    }

    const form = useForm({
        nombre: props.lugarhospedaje.nombre,
        descripcion: props.lugarhospedaje.descripcion,
        direccion: props.lugarhospedaje.direccion,
        telefono: props.lugarhospedaje.vegano,
        email: props.lugarhospedaje.celiaco,
        web: props.lugarhospedaje.web
    });

    const handleSubmit = () => {
        form.put(route('lugareshospedaje.update', props.lugarhospedaje.id), {
            onSuccess: () => {
                console.log('Lugar de hospedaje actualizado exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Comida">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Editar Lugar de Hospedaje</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('lugareshospedaje.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <LugarHospedajeForm 
                            :updating="true" 
                            :form="form" 
                            @submit="handleSubmit"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

</template>