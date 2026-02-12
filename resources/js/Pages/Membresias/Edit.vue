<script>
    export default {
        name: 'MembresiasEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import MembresiaForm from '@/Components/Formularios/MembresiaForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        membresia:{
            type: Object,
            required: true
        },

        entidades: {
            type: Array,
            default: () => []
        },
        botonesPago: {
            type: Array,
            default: () => []
        }
    });
    // console.log(props.membresia);
    if (!props.membresia) {
        console.error('El membresia no está definido');
    }

    const form = useForm({
        nombre: props.membresia.nombre,
        descripcion: props.membresia.descripcion,
        entidad_id: props.membresia.entidad_id,
        botonpago_id: props.membresia.botonpago_id || null,
        valor: props.membresia.valor
    });

    const handleSubmit = () => {
        form.put(route('membresias.update', props.membresia.id), {
            onSuccess: () => {
                console.log('Membresia actualizado exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Membresia">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Editar membresia</h1>
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
                            :updating="true" 
                            :form="form" 
                            :entidades="entidades"
                            :botonesPago="botonesPago"
                            @submit="handleSubmit"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

</template>
