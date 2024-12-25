<script>
    export default {
        name: 'MetodosPagoEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import MetodoPagoForm from '@/Components/Formularios/MetodoPagoForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        metodoPago:{
            type: Object,
            required: true
        }
    });
    // console.log(props.metodoPago);
    if (!props.metodoPago) {
        console.error('La Método de pago no está definida');
    }
    console.log(props.metodoPago.nombre);
    const form = useForm({
        nombre: props.metodoPago.nombre,
        descripcion: props.metodoPago.descripcion
    });

    const handleSubmit = () => {
        form.put(route('metodospago.update', props.metodoPago.id), {
            onSuccess: () => {
                console.log('Método de pago actualizado exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Método de pago">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Editar Método de pago</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('metodospago.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <MetodoPagoForm 
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