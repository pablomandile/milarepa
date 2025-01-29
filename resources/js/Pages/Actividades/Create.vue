<script>
    export default {
        name: 'ActividadesCreate'
    }
</script>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AppLayout from '@/Layouts/AppLayout.vue'
    import ActividadForm from '@/Components/Formularios/ActividadForm.vue'
    import { Link } from '@inertiajs/vue3';
    import ScrollTop from 'primevue/scrolltop';

    const props = defineProps({
        tiposActividad: {
        type: Array,
        default: () => [],
        },
        entidades: {
            type: Array,
            default: () => [],
        },
        descripciones: {
            type: Array,
            default: () => [],
        },
        disponibilidades: {
            type: Array,
            default: () => [],
        },
        modalidades: {
            type: Array,
            default: () => [],
        },
        esquema_precios: {
            type: Array,
            default: () => [],
        },
        esquema_descuentos: {
            type: Array,
            default: () => [],
        },
        streams: {
            type: Array,
            default: () => [],
        },
        programas: {
            type: Array,
            default: () => [],
        },
        metodosPago: {
            type: Array,
            default: () => [],
        },
        hospedajes: {
            type: Array,
            default: () => [],
        },
        comidas: {
            type: Array,
            default: () => [],
        },
        transportes: {
            type: Array,
            default: () => [],
        },
    });


    const form = useForm({
        tipo_actividad_id: null,
        nombre: '',
        descripcion_id: '',
        observaciones: '',
        imagen_id: null,
        fecha_inicio: null,
        fecha_fin: null,
        pagoAmticipado: null,
        entidad_id: null,
        disponibilidad_id: null,
        maestros_ids: [],
        coordinadores_ids: [],
        modalidad_id: null,
        esquema_precio_id: null,
        esquema_descuento_id: null,
        link_grabacion: '',
        link_web: '',
        stream_id: false,
        programa_id: null,
        metodos_pago_ids: [],
        hospedajes_ids: [],
        comidas_ids: [],
        transportes_ids: [],
    });

</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight" >Agregar una Actividad</h1>
        </template>
        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <!-- Botón de Volver -->
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link 
                            :href="route('actividades.index')" 
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <ActividadForm 
                            :updating="false"
                            :tiposActividad="tiposActividad"
                            :descripciones="descripciones"
                            :entidades="entidades"
                            :disponibilidades="disponibilidades"
                            :modalidades="modalidades"
                            :esquema_precios="esquema_precios"
                            :esquema_descuentos="esquema_descuentos"
                            :streams="streams"
                            :programas="programas"
                            :metodosPago="metodosPago"
                            :hospedajes="hospedajes"
                            :comidas="comidas"
                            :transportes="transportes"
                            :form="form" @submit="form.post(route('actividades.store'))"/>
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