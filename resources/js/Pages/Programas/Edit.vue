<script>
    export default {
        name: 'ProgramasEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import ProgramaForm from '@/Components/Formularios/ProgramaForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        programa:{
            type: Object,
            required: true
        }
    });

    if (!props.programa) {
        console.error('La descripción no está definida');
    }

    const form = useForm({
        nombre: props.programa.nombre,
        programa: props.programa.programa
    });

    const handleSubmit = () => {
        form.put(route('programas.update', props.programa.id), {
            onSuccess: () => {
                console.log('Programa actualizado exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Programa">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Editar Programa</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('programas.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <ProgramaForm 
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