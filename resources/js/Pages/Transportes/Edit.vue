<script>
    export default {
        name: 'TransportesEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import TransporteForm from '@/Components/Formularios/TransporteForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        transporte:{
            type: Object,
            required: true
        },
        botonesPago: {
            type: Array,
            default: () => []
        }
    });
    if (!props.transporte) {
        console.error('El lugar de Hospedaje no está definido');
    }

    const form = useForm({
        descripcion: props.transporte.descripcion,
        botonpago_id: props.transporte.botonpago_id || null,
        precio: props.transporte.precio
    });

    const handleSubmit = () => {
        form.put(route('transportes.update', props.transporte.id), {
            onSuccess: () => {
                console.log('Transporte actualizado exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Transporte">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Editar Transporte</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('transportes.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <TransporteForm 
                            :updating="true" 
                            :form="form" 
                            :botonesPago="props.botonesPago"
                            @submit="handleSubmit"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

</template>
