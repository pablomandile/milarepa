<script>
    export default {
        name: 'DescripcionesEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import DescripcionForm from '@/Components/Formularios/DescripcionForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        descripcion:{
            type: Object,
            required: true
        }
    });
    if (!props.descripcion) {
        console.error('La descripción no está definida');
    }

    const form = useForm({
        nombre: props.descripcion.nombre,
        descripcion: props.descripcion.descripcion
    });

    const handleSubmit = () => {
        form.put(route('descripciones.update', props.descripcion.id), {
            onSuccess: () => {
                console.log('Descripcion actualizada exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Descripción">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Editar Descripción</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('descripciones.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <DescripcionForm 
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