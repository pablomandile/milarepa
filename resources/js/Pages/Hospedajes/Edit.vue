<script>
    export default {
        name: 'HospedajesEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import HospedajeForm from '@/Components/Formularios/HospedajeForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        hospedaje:{
            type: Object,
            required: true
        },

        lugaresHospedaje: {
        type: Array,
        default: () => []
        },
        botonesPago: {
        type: Array,
        default: () => []
        }
    });

    // console.log(props.hospedaje);
    if (!props.hospedaje) {
        console.error('El hospedaje no está definido');
    }

    const form = useForm({
        nombre: props.hospedaje.nombre,
        descripcion: props.hospedaje.descripcion,
        botonpago_id: props.hospedaje.botonpago_id || null,
        precio: props.hospedaje.precio,
        lugar_hospedaje_id: props.hospedaje.lugar_hospedaje_id
    });

    const handleSubmit = () => {
        form.put(route('hospedajes.update', props.hospedaje.id), {
            onSuccess: () => {
                console.log('Hospedaje actualizado exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Acomodación">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Editar Acomodación</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('hospedajes.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <HospedajeForm 
                            :updating="true" 
                            :form="form" 
                            :lugaresHospedaje="lugaresHospedaje"
                            :botonesPago="props.botonesPago"
                            @submit="handleSubmit"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

</template>
