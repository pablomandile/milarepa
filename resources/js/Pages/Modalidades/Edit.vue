<script>
    export default {
        name: 'ModalidadesEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import ModalidadForm from '@/Components/Formularios/ModalidadForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        modalidad:{
            type: Object,
            required: true
        }
    });

    if (!props.modalidad) {
        console.error('La modalidad no está definida');
    }

    const form = useForm({
        nombre: props.modalidad.nombre
    });

    const handleSubmit = () => {
        form.put(route('modalidades.update', props.modalidad.id), {
            onSuccess: () => {
                console.log('Modalidad actualizada exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Modalidad">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Editar Modalidad</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('modalidades.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <ModalidadForm 
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