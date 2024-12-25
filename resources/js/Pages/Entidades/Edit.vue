<script>
    export default {
        name: 'EntidadesEdit'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue';
    import EntidadForm from '@/Components/Formularios/EntidadForm.vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        entidad:{
            type: Object,
            required: true
        }
    });
    // console.log(props.entidad);
    if (!props.entidad) {
        console.error('La Entidad no está definido');
    }

    const form = useForm({
        nombre: props.entidad.nombre,
        descripcion: props.entidad.descripcion,
        abreviacion:props.entidad.abreviacion,
        direccion: props.entidad.direccion,
        telefono: props.entidad.telefono,
        whatsapp: props.entidad.whatsapp,
        web_uri: props.entidad.web_uri,
        instagram_uri: props.entidad.instagram_uri,
        facebook_uri: props.entidad.facebook_uri,
        twitter_uri: props.entidad.twitter_uri,
        youtube_uri: props.entidad.youtube_uri,
        spotify_uri: props.entidad.spotify_uri,
        logo_uri: props.entidad.logo_uri,
        email1: props.entidad.email1,
        email2: props.entidad.email2,
        entidad_principal: props.entidad.entidad_principal === 1,
    });

    const handleSubmit = () => {
        form.put(route('entidades.update', props.entidad.id), {
            onSuccess: () => {
                console.log('Entidad actualizada exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Entidad">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Editar entidad</h1>
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