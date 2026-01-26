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
    import { Inertia } from '@inertiajs/inertia';


    function reloadDescripciones() {
    // Llama a Inertia.reload pidiendo solo la prop `descripciones` y preservando el resto
        Inertia.reload({
            only: ['descripciones'],
            preserveState: true,
            preserveScroll: true,
        });
    } 

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
        grabaciones: {
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
        maestros: {
            type: Array,
            default: () => [],
        },
        coordinadores: {
            type: Array,
            default: () => [],
        }
    });


    const form = useForm({
        tipo_actividad_id: null,
        nombre: '',
        descripcion_id: null,
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
        link_web: '',
        stream_id: null,
        grabación_id: null,
        programa_id: null,
        metodos_pago_ids: [],
        hospedajes_ids: [],
        comidas_ids: [],
        transportes_ids: [],
        maestros_ids: [],
        coordinadores_ids: []
    });

    function handleSubmit() {
        // Formatear las fechas usando transform de Inertia
        form.transform((data) => {
            // Función para formatear fechas
            function formatDatetime(date) {
              if (!date) return null;
              
              if (date instanceof Date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                const seconds = String(date.getSeconds()).padStart(2, '0');
                return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
              }
              
              if (typeof date === 'string') {
                const dateObj = new Date(date);
                const year = dateObj.getFullYear();
                const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                const day = String(dateObj.getDate()).padStart(2, '0');
                const hours = String(dateObj.getHours()).padStart(2, '0');
                const minutes = String(dateObj.getMinutes()).padStart(2, '0');
                const seconds = String(dateObj.getSeconds()).padStart(2, '0');
                return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
              }
              
              return date;
            }
            
            return {
                ...data,
                fecha_inicio: formatDatetime(data.fecha_inicio),
                fecha_fin: formatDatetime(data.fecha_fin),
                pagoAmticipado: formatDatetime(data.pagoAmticipado),
            };
        }).post(route('actividades.store'));
    }

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

                            <!-- TITULO/ DESCRIPCION full-width encima del form -->
                            <div class="mb-6">
                              <h2 class="text-2xl font-semibold text-indigo-600">
                                Nueva Actividad
                              </h2>
                              <p class="text-sm text-gray-600 mt-1">
                                Completa los datos para registrar una nueva actividad.
                              </p>
                            </div>

                            <!-- Formulario: indicamos que oculte su header interno -->
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
                              :grabaciones="grabaciones"
                              :programas="programas"
                              :metodosPago="metodosPago"
                              :hospedajes="hospedajes"
                              :comidas="comidas"
                              :transportes="transportes"
                              :coordinadores="coordinadores"
                              :maestros="maestros"
                              :form="form"
                              :hide-header="true"
                              @submit="handleSubmit"
                              @refresh-descripciones="reloadDescripciones"
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