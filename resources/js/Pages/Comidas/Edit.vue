<script>
    export default {
        name: 'ComidasEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import ComidaForm from '@/Components/Formularios/ComidaForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        comida:{
            type: Object,
            required: true
        }
    });
    // console.log(props.comida);
    if (!props.comida) {
        console.error('El comida no está definido');
    }

    const form = useForm({
        nombre: props.comida.nombre,
        descripcion: props.comida.descripcion,
        precio: props.comida.precio,
        vegano: props.comida.vegano === 1,
        celiaco: props.comida.celiaco === 1
    });

    const handleSubmit = () => {
        form.put(route('comidas.update', props.comida.id), {
            onSuccess: () => {
                console.log('Comida actualizada exitosamente');
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
            <h1 class="font-semibold text-lx text-gray-800 leading-tight" >Editar comida</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('comidas.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <ComidaForm 
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