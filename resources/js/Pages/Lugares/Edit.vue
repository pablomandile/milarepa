<script>
export default {
    name: 'LugaresEdit',
};
</script>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import LugarForm from '@/Components/Formularios/LugarForm.vue';

const props = defineProps({
    lugar: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    nombre: props.lugar.nombre,
    descripcion: props.lugar.descripcion,
    direccion: props.lugar.direccion,
    telefono: props.lugar.telefono,
    whatsapp: props.lugar.whatsapp,
    web_uri: props.lugar.web_uri,
    instagram_uri: props.lugar.instagram_uri,
    logo_uri: props.lugar.logo_uri,
    email1: props.lugar.email1,
    email2: props.lugar.email2,
});

const handleSubmit = () => {
    form.put(route('lugares.update', props.lugar.id));
};
</script>

<template>
    <AppLayout title="Editar Lugar">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Editar lugar</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link :href="route('lugares.index')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <LugarForm :updating="true" :form="form" @submit="handleSubmit" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
