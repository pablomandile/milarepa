<script setup>
import { useForm } from '@inertiajs/vue3';
import GrabacionForm from '@/Components/Formularios/GrabacionForm.vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    grabacion: {
        type: Object,
        required: true
    },
    botonesPago: {
        type: Array,
        default: () => []
    }
});

const form = useForm({
    nombre: props.grabacion.nombre || '',
    botonpago_id: props.grabacion.botonpago_id || null,
    valor: props.grabacion.valor ?? 0
});

const submit = () => {
    form.put(route('grabaciones.update', props.grabacion.id));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Editar Grabación</h1>
        </template>
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <div class="flex justify-between mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('grabaciones.index')" 
                            class="ml-4 text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                        <Link
                            :href="route('grabaciones.links', props.grabacion.id)"
                            class="text-white bg-emerald-500 hover:bg-emerald-700 py-2 px-4 rounded"
                        >
                            Agregar links
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <GrabacionForm 
                                :updating="true"
                                :form="form"
                                :botonesPago="props.botonesPago"
                                @submit="submit"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
