<script>
    export default {
        name: 'TiposactividadEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import TipoActividadForm from '@/Components/Formularios/TipoActividadForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        tipoActividad:{
            type: Object,
            required: true
        }
    });
    
    // console.log(props.tiposActividad);
    if (!props.tipoActividad) {
        console.error('El tipo de actividad no está definido');
    }
    const form = useForm({
        nombre: props.tipoActividad.nombre, // ahora será la descripción
        abreviacion: props.tipoActividad.abreviacion || '' // ahora será el nombre
    });

    const handleSubmit = () => {
        form.put(route('tiposactividad.update', props.tipoActividad.id), {
            onSuccess: () => {
                console.log('Tipo de Actividad actualizado exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Tipo de Actividad">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Editar Tipo de Actividad</h1>
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