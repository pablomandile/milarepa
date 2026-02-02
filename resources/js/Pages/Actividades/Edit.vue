<script>
    export default {
        name: 'ActividadesEdit'
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
        Inertia.reload({
            only: ['descripciones'],
            preserveState: true,
            preserveScroll: true,
        });
    } 

    const props = defineProps({
        actividad: {
            type: Object,
            required: true
        },
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
        lugaresHospedaje: {
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

    if (!props.actividad) {
        console.error('La actividad no está definida');
    }

    // Función para convertir string ISO a Date object
    function parseDateTime(isoString) {
        if (!isoString) return null;
        // Convierte "2026-01-27T22:00:25.000000Z" a un Date object
        return new Date(isoString);
    }

    const form = useForm({
        tipo_actividad_id: props.actividad.tipo_actividad_id,
        nombre: props.actividad.nombre,
        descripcion_id: props.actividad.descripcion_id,
        observaciones: props.actividad.observaciones,
        imagen_id: props.actividad.imagen_id,
        fecha_inicio: parseDateTime(props.actividad.fecha_inicio),
        fecha_fin: parseDateTime(props.actividad.fecha_fin),
        pagoAmticipado: parseDateTime(props.actividad.pagoAmticipado),
        entidad_id: props.actividad.entidad_id,
        disponibilidad_id: props.actividad.disponibilidad_id,
        modalidad_id: props.actividad.modalidad_id,
        esquema_precio_id: props.actividad.esquema_precio_id,
        esquema_descuento_id: props.actividad.esquema_descuento_id,
        link_web: props.actividad.link_web,
        stream_id: props.actividad.stream_id,
        grabacion_id: props.actividad.grabacion_id,
        programa_id: props.actividad.programa_id,
        estado: props.actividad.estado,
        metodos_pago_ids: props.actividad.metodos_pago ? props.actividad.metodos_pago.map(m => m.id) : [],
        hospedajes_ids: props.actividad.hospedajes ? props.actividad.hospedajes.map(h => h.id) : [],
        lugar_hospedaje_id: props.actividad.hospedajes && props.actividad.hospedajes.length > 0
            ? props.actividad.hospedajes[0].lugar_hospedaje_id
            : null,
        comidas_ids: props.actividad.comidas ? props.actividad.comidas.map(c => c.id) : [],
        transportes_ids: props.actividad.transportes ? props.actividad.transportes.map(t => t.id) : [],
        maestros_ids: props.actividad.maestros ? props.actividad.maestros.map(m => m.id) : [],
        coordinadores_ids: props.actividad.coordinadores ? props.actividad.coordinadores.map(c => c.id) : []
    });

    const handleSubmit = () => {
        // Convertir Date objects a strings formateados
        form.transform((data) => {
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
              
              return date;
            }
            
            return {
                ...data,
                fecha_inicio: formatDatetime(data.fecha_inicio),
                fecha_fin: formatDatetime(data.fecha_fin),
                pagoAmticipado: formatDatetime(data.pagoAmticipado),
            };
        }).put(route('actividades.update', { actividad: props.actividad.id }), {
            onSuccess: () => {
                console.log('Actividad actualizada exitosamente');
            },
            onError: errors => {
                console.log('Errores al actualizar:', errors);
            }
        });
    }
</script>

<template>
    <AppLayout title="Editar Actividad">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Editar Actividad</h1>
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
                                Actualizar Actividad
                              </h2>
                              <p class="text-sm text-gray-600 mt-1">
                                Edita la información de la actividad seleccionada.
                              </p>
                            </div>

                            <!-- Formulario: indicamos que oculte su header interno -->
                            <ActividadForm 
                              :updating="true"
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
                              :lugaresHospedaje="lugaresHospedaje"
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
            class="bg-slate-300"
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
