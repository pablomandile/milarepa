<script>
    export default {
        name: 'EntidadesCreate'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue'
    import EntidadForm from '@/Components/Formularios/EntidadForm.vue'
    import { Link } from '@inertiajs/vue3';
    import ScrollTop from 'primevue/scrolltop';

    const form = useForm({
        nombre: '',
        descripcion: '',
        direccion: '',
        telefono: '',
        whatsapp: '',
        web_uri: '',
        instagram_uri: '',
        facebook_uri: '',
        twitter_uri: '',
        youtube_uri: '',
        logo_uri: '',
        email1: '',
        email2: '',
        entidad_principal: false
    })
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Agregar una Entidad</h1>
        </template>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('entidades.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <EntidadForm 
                            :updating="false"
                            :form="form" @submit="form.post(route('entidades.store'))"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <ScrollTop
            :threshold="100"
            icon="pi pi-angle-up"
            :pt="{
                root: 'w-2rem h-2rem border-round-sm',
                icon: {
                    class: 'text-indigo-400'
                }
            }"
        />         
    </AppLayout>

</template>