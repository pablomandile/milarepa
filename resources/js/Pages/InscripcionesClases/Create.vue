<script>
export default {
    name: 'InscripcionesClasesCreate'
}
</script>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InscripcionClaseForm from '@/Components/Formularios/InscripcionClaseForm.vue';

const props = defineProps({
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

const form = useForm({
    email: '',
    nombre: '',
    provincia_id: '',
    municipio_id: '',
    barrio_id: '',
    direccion: '',
    telefono: '',
    registrar_datos: true,
    existing_user_id: null,
    entidad_id: '',
    clase_id: '',
    membresia: '',
    precioGeneral: 0,
    montoActividad: 0,
    montoTharpa: 0,
    articulos_tharpa: '',
    montoTienda: 0,
    articulos_tienda: '',
    libro_ids: [],
    oracion_ids: [],
    arte_ids: [],
    otro_ids: [],
    montoApagar: 0,
    pago: '',
    metodo_pago_id: null,
    monto_cobrado: null,
    online: false,
});

const submit = () => {
    form.post(route('inscripciones-clases.store'));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Registro de Inscripción a Clase</h1>
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
                            button-label="Registrar"
                            @submit="submit"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
