<script>
    export default {
        name: 'MonedasEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import MonedaForm from '@/Components/Formularios/MonedaForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        moneda:{
            type: Object,
            required: true
        }
    });
    // console.log(props.moneda);
    if (!props.moneda) {
        console.error('La moneda no está definida');
    }

    const form = useForm({
        nombre: props.moneda.nombre,
        simbolo: props.moneda.simbolo
    });

    const handleSubmit = () => {
        form.put(route('monedas.update', props.moneda.id), {
            onSuccess: () => {
                console.log('Moneda actualizada exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Moneda">
        <template #header>
            <h1 class="font-semibold text-lx text-gray-800 leading-tight" >Editar moneda</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('monedas.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <MonedaForm 
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