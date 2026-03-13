<script>
export default {
    name: 'ClasesEdit'
}
</script>

<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ClaseForm from '@/Components/Formularios/ClaseForm.vue';

const props = defineProps({
    clase: {
        type: Object,
        required: true
    },
    ciclos: {
        type: Array,
        default: () => []
    },
    entidades: {
        type: Array,
        default: () => []
    },
    maestros: {
        type: Array,
        default: () => []
    },
    coordinadores: {
        type: Array,
        default: () => []
    },
    esquemaPrecios: {
        type: Array,
        default: () => []
    },
    modalidades: {
        type: Array,
        default: () => []
    },
    streams: {
        type: Array,
        default: () => []
    },
});

const form = useForm({
    nombre: props.clase.nombre,
    ciclo_id: props.clase.ciclo_id,
    entidad_id: props.clase.entidad_id || null,
    mes_referencia: props.clase.mes_referencia || '',
    descripcion: props.clase.descripcion || '',
    imagen_id: props.clase.imagen_id || null,
    dias_semana: Array.isArray(props.clase.dias_semana) ? props.clase.dias_semana : [],
    titulos_por_fecha: props.clase.titulos_por_fecha && typeof props.clase.titulos_por_fecha === 'object' ? props.clase.titulos_por_fecha : {},
    horario_desde: props.clase.horario_desde ? String(props.clase.horario_desde).slice(0, 5) : '',
    horario_hasta: props.clase.horario_hasta ? String(props.clase.horario_hasta).slice(0, 5) : '',
    maestro_ids: Array.isArray(props.clase.maestros)
        ? props.clase.maestros.map((maestro) => maestro.id)
        : (props.clase.maestro_id ? [props.clase.maestro_id] : []),
    coordinador_id: props.clase.coordinador_id,
    esquema_precio_id: props.clase.esquema_precio_id,
    modalidad_id: props.clase.modalidad_id ?? null,
    stream_id: props.clase.stream_id ?? null,
    mostrar_en_calendario: !!props.clase.mostrar_en_calendario,
    activa: props.clase.activa ?? true,
});

const CATALOGOS_RECARGABLES = new Set(['ciclos', 'entidades']);

function reloadCatalogo(catalogo) {
    if (!CATALOGOS_RECARGABLES.has(catalogo)) return;

    router.reload({
        only: [catalogo],
        preserveState: true,
        preserveScroll: true,
    });
}

const handleSubmit = () => {
    form.put(route('clases.update', props.clase.id));
};
</script>

<template>
    <AppLayout title="Editar Clase">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Editar Clase</h1>
        </template>

        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link :href="route('clases.index')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <div class="mb-6">
                                <h2 class="text-2xl font-semibold text-indigo-600">Actualizar Clase</h2>
                                <p class="text-sm text-gray-600 mt-1">Edita la informacion de la clase seleccionada.</p>
                            </div>

                            <ClaseForm
                                :updating="true"
                                :form="form"
                                :ciclos="ciclos"
                                :entidades="entidades"
                                :maestros="maestros"
                                :coordinadores="coordinadores"
                                :esquema-precios="esquemaPrecios"
                                :modalidades="modalidades"
                                :streams="streams"
                                :imagen-preview-url="props.clase.imagen ? `/storage/${props.clase.imagen.ruta}` : ''"
                                :hide-header="true"
                                @submit="handleSubmit"
                                @refresh-catalogo="reloadCatalogo"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
