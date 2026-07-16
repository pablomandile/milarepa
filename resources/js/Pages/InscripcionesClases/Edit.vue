<script>
export default {
    name: 'InscripcionesClasesEdit'
}
</script>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InscripcionClaseForm from '@/Components/Formularios/InscripcionClaseForm.vue';

const props = defineProps({
    inscripcionClase: {
        type: Object,
        required: true,
    },
    clases: {
        type: Array,
        default: () => [],
    },
    entidades: {
        type: Array,
        default: () => [],
    },
    librosTharpa: {
        type: Array,
        default: () => [],
    },
    oracionesTharpa: {
        type: Array,
        default: () => [],
    },
    arteTharpa: {
        type: Array,
        default: () => [],
    },
    otrosTharpa: {
        type: Array,
        default: () => [],
    },
    provincias: {
        type: Array,
        default: () => [],
    },
    municipios: {
        type: Array,
        default: () => [],
    },
    barrios: {
        type: Array,
        default: () => [],
    },
    metodosPago: {
        type: Array,
        default: () => [],
    },
});

const persona = props.inscripcionClase.user || props.inscripcionClase.guest_user || {};
const claseActual = props.clases.find((clase) => Number(clase.id) === Number(props.inscripcionClase.clase_id));

// Precarga por ID desde los items de la inscripción (no por título).
const itemsPrevios = props.inscripcionClase.items || [];
const idsPorCategoria = (categoria) => itemsPrevios
    .filter((item) => item.categoria === categoria)
    .map((item) => Number(item.producto_id));

const form = useForm({
    email: props.inscripcionClase.email_snapshot || persona.email || '',
    nombre: props.inscripcionClase.nombre_snapshot || persona.name || '',
    provincia_id: persona.provincia_id || '',
    municipio_id: persona.municipio_id || '',
    barrio_id: persona.barrio_id || '',
    direccion: persona.direccion || '',
    telefono: persona.telefono || '',
    registrar_datos: !!props.inscripcionClase.user_id,
    existing_user_id: props.inscripcionClase.user_id || null,
    entidad_id: claseActual?.entidad_id || props.inscripcionClase.clase?.entidad_id || '',
    clase_id: props.inscripcionClase.clase_id || '',
    membresia: props.inscripcionClase.membresia || '',
    precioGeneral: Number(props.inscripcionClase.precioGeneral || 0),
    montoActividad: Number(props.inscripcionClase.montoActividad || 0),
    montoTharpa: Number(props.inscripcionClase.montoTharpa || 0),
    articulos_tharpa: props.inscripcionClase.articulos_tharpa || '',
    montoTienda: Number(props.inscripcionClase.montoTienda || 0),
    articulos_tienda: props.inscripcionClase.articulos_tienda || '',
    libro_ids: idsPorCategoria('libro'),
    oracion_ids: idsPorCategoria('oracion'),
    arte_ids: idsPorCategoria('arte'),
    otro_ids: idsPorCategoria('otro'),
    montoApagar: Number(props.inscripcionClase.montoApagar || 0),
    pago: props.inscripcionClase.pago || '',
    metodo_pago_id: null,
    monto_cobrado: null,
    online: !!props.inscripcionClase.online,
});

const submit = () => {
    form.put(route('inscripciones-clases.update', props.inscripcionClase.id));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Editar Inscripción a Clase</h1>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link
                            :href="route('inscripciones-clases.index')"
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"
                        >
                            Volver
                        </Link>
                    </div>
                    <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <InscripcionClaseForm
                            :form="form"
                            :clases="clases"
                            :entidades="entidades"
                            :libros-tharpa="librosTharpa"
                            :oraciones-tharpa="oracionesTharpa"
                            :arte-tharpa="arteTharpa"
                            :otros-tharpa="otrosTharpa"
                            :provincias="provincias"
                            :municipios="municipios"
                            :barrios="barrios"
                            :metodos-pago="metodosPago"
                            button-label="Actualizar"
                            @submit="submit"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
