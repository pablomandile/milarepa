<script>
    export default {
        name: 'CompleteProfile'
    }
</script>

<script setup>
    import { Head, useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue'
    import CompleteProfileForm from '@/Components/Formularios/CompleteProfileForm.vue'
    import { Link } from '@inertiajs/vue3';
    import ScrollTop from 'primevue/scrolltop';

    const props = defineProps({
        membresias: {
            type: Array,
            default: () => []
        },
        paises: {
            type: Array,
            default: () => []
        },
        localidades: {
            type: Array,
            default: () => []
        },
        sexos: {
            type: Array,
            default: () => []
        }
    });

    const form = useForm({
    accesibilidad: false,
    accesibilidad_desc: '',
    direccion: '',
    pais_id: '',
    localidad_id: '',
    telefono: '',
    whatsapp: '',
    edad: '',
    sexo_id: '',
    membresia_id: '',
    es_maestro: false,
    es_coordinador: false,
    perfil_completo: false
    });

</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Agregar datos a tu Perfil</h1>
        </template>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <CompleteProfileForm 
                            :updating="false"
                            :membresias="membresias"
                            :paises="paises"
                            :localidades="localidades"
                            :sexos="sexos"
                            :form="form" @submit="form.post(route('profile.complete.store'))"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ScrollTop
            class="bg-slate-300	"
            :threshold="100"
            icon="pi pi-angle-up"
            :pt="{
                root: 'w-2rem h-2rem border-round-sm',
                icon: {
                    class: 'text-indigo-500'
                }
            }"
        />         
    </AppLayout>

</template>
