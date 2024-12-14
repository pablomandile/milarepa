<script>
    export default {
        name: 'MaestrosEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import MaestroForm from '@/Components/Formularios/MaestroForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        maestro:{
            type: Object,
            required: true
        }
    });
    // console.log(props.maestro);
    if (!props.maestro) {
        console.error('El maestro no está definido');
    }

    const form = useForm({
        nombre: props.maestro.nombre,
        telefono: props.maestro.telefono,
        email: props.maestro.email
    });

    const handleSubmit = () => {
        form.put(route('maestros.update', props.maestro.id), {
            onSuccess: () => {
                console.log('Maestro actualizado exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Maestro">
        <template #header>
            <h1 class="font-semibold text-lx text-gray-800 leading-tight" >Editar maestro</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('maestros.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <MaestroForm 
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