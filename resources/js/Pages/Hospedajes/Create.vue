<script>
    export default {
        name: 'HospedajesCreate'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import HospedajeForm from '@/Components/Formularios/HospedajeForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        lugaresHospedaje: {
        type: Array,
        default: () => []
        },
        botonesPago: {
        type: Array,
        default: () => []
        }
    });

    const form = useForm({
        nombre: '',
        descripcion: '',
        botonpago_id: null,
        precio: '',
        lugar_hospedaje_id: ''
    });

</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Agregar Nuevo Hospedaje</h1>
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
                            :updating="false"
                            :lugaresHospedaje="lugaresHospedaje"
                            :botonesPago="props.botonesPago"
                            :form="form" @submit="form.post(route('hospedajes.store'))"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
