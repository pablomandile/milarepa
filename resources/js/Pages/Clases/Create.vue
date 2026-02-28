<script>
export default {
    name: 'ClasesCreate'
}
</script>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ClaseForm from '@/Components/Formularios/ClaseForm.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
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
});

const form = useForm({
    nombre: '',
    ciclo_id: null,
    entidad_id: null,
    mes_referencia: '',
    descripcion: '',
    imagen_id: null,
    dias_semana: [],
    titulos_por_fecha: {},
    horario_desde: '',
    horario_hasta: '',
    maestro_id: null,
    coordinador_id: null,
    esquema_precio_id: null,
    mostrar_en_calendario: false,
});
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Nueva Clase</h1>
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
                                <h2 class="text-2xl font-semibold text-indigo-600">Nueva Clase</h2>
                                <p class="text-sm text-gray-600 mt-1">Completa los datos para registrar una nueva clase.</p>
                            </div>

                            <ClaseForm
                                :updating="false"
                                :form="form"
                                :ciclos="ciclos"
                                :entidades="entidades"
                                :maestros="maestros"
                                :coordinadores="coordinadores"
                                :esquema-precios="esquemaPrecios"
                                :imagen-preview-url="''"
                                :hide-header="true"
                                @submit="form.post(route('clases.store'))"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
