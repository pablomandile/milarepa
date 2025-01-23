<script>
    export default {
        name: 'CompleteProfile'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue'
    import CompleteProfileForm from '@/Components/Formularios/CompleteProfileForm.vue'
    import ScrollTop from 'primevue/scrolltop';

    const props = defineProps({
        user: {
            type: Object,
            default: () => ({}),
        },
        membresias: {
            type: Array,
            default: () => [],
        },
        paises: {
            type: Array,
            default: () => [],
        },
        localidades: {
            type: Array,
            default: () => [],
        },
        sexos: {
            type: Array,
            default: () => [],
        },
        updating: {
            type: Boolean,
            default: false,
        },
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

    if (props.updating && props.user) {
        form.accesibilidad = props.user.accesibilidad === 1;
        form.accesibilidad_desc = props.user.accesibilidad_desc ?? '';
        form.direccion = props.user.direccion ?? '';
        form.pais_id = props.user.pais_id ?? '';
        form.localidad_id = props.user.localidad_id ?? '';
        form.telefono = props.user.telefono ?? '';
        form.whatsapp = props.user.whatsapp ?? '';
        form.edad = props.user.edad ?? '';
        form.sexo_id = props.user.sexo_id ?? '';
        form.membresia_id = props.user.membresia_id ?? '';
        form.es_maestro = props.user.es_mastro;
        form.es_coordinador = props.user.es_coordinador;
        form.msgxmail = props.user.msgxmail === 1;
        form.msgxwapp = props.user.msgxwapp === 1;
    }

    function submit() {
        if (props.updating) {
            // Si estamos en modo edición
            form.put(route('profile.complete.update'), {
            onSuccess: () => {
                // Opcional: algo tras éxito
            },
            });
        } else {
            // Si estamos en modo "crear" o "completar" inicial
            form.post(route('profile.complete.store'), {
            onSuccess: () => {
                // Opcional
            },
            });
        }
    }

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
                            :updating="updating"
                            :membresias="membresias"
                            :paises="paises"
                            :localidades="localidades"
                            :sexos="sexos"
                            :form="form" 
                            @submit="submit"
                        />
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
