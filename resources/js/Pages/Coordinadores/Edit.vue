<script>
    export default {
        name: 'CoordinadoresEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import CoordinadorForm from '@/Components/Formularios/CoordinadorForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        coordinador:{
            type: Object,
            required: true
        }
    });
    // console.log(props.coordinador);
    if (!props.coordinador) {
        console.error('El coordinador no está definido');
    }

    const form = useForm({
        nombre: props.coordinador.nombre,
        telefono: props.coordinador.telefono,
        email: props.coordinador.email
    });

    const handleSubmit = () => {
        form.put(route('coordinadores.update', props.coordinador.id), {
            onSuccess: () => {
                console.log('Coordinador actualizado exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Coordinador">
        <template #header>
            <h1 class="font-semibold text-lx text-gray-800 leading-tight" >Editar coordinador</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('coordinadores.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <CoordinadorForm 
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