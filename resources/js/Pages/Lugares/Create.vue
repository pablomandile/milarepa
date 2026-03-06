<script>
export default {
    name: 'LugaresCreate',
};
</script>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import LugarForm from '@/Components/Formularios/LugarForm.vue';
import ScrollTop from 'primevue/scrolltop';

const form = useForm({
    nombre: '',
    descripcion: '',
    direccion: '',
    telefono: '',
    whatsapp: '',
    web_uri: '',
    instagram_uri: '',
    logo_uri: '',
    email1: '',
    email2: '',
});
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Agregar un Lugar</h1>
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
                            <LugarForm :updating="false" :form="form" @submit="form.post(route('lugares.store'))" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ScrollTop
            class="bg-slate-300"
            :threshold="100"
            icon="pi pi-angle-up"
            :pt="{
                root: 'w-2rem h-2rem border-round-sm',
                icon: { class: 'text-indigo-500' },
            }"
        />
    </AppLayout>
</template>
