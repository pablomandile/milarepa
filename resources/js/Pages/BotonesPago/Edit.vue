<script>
    export default {
        name: 'BotonesPagoEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import BotonPagoForm from '@/Components/Formularios/BotonPagoForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        boton:{
            type: Object,
            required: true
        },
        metodosPago: {
            type: Array,
            required: true
        }
    });
    
    const form = useForm({
        nombre: props.boton.nombre || '',
        descripcion: props.boton.descripcion || '',
        link: props.boton.link || '',
        metodo_pago_id: props.boton.metodo_pago_id || null
    });

    const handleSubmit = () => {
        form.put(route('botonespago.update', props.boton.id));
    }
</script>

<template>
    <AppLayout title="Editar Botón de Pago">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Editar Botón de Pago</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('botonespago.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <BotonPagoForm 
                            :updating="true" 
                            :form="form"
                            :metodosPago="metodosPago"
                            @submit="handleSubmit"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

</template>
