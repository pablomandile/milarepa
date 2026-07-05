<script>
export default {
    name: 'OracionesCantadasCreate'
}
</script>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import OracionCantadaForm from '@/Components/Formularios/OracionCantadaForm.vue';

defineProps({
    streams: {
        type: Array,
        default: () => [],
    },
    modalidades: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    nombre: '',
    descripcion: '',
    dia: 1,
    dias_semana: [],
    hora: '08:00',
    horarios_por_dia: {},
    periodicidad: 'Mensual',
    configuracion_por_mes: [],
    excepciones_por_fecha: [],
    modalidad_id: null,
    stream_id: null,
    imagen: '',
    mostrar_en_calendario: false,
});
</script>

<template>
    <AppLayout title="Nueva Oración Cantada">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Nueva Oración Cantada</h1>
        </template>

        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Nueva Oracion Cantada</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Agregando una nueva oracion cantada al sistema.</p>
                            </div>
                            <Link :href="route('oracionescantadas.index')" class="shrink-0 text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                                Volver
                            </Link>
                        </div>

                        <OracionCantadaForm
                            :updating="false"
                            :hide-header="true"
                            :form="form"
                            :streams="streams"
                            :modalidades="modalidades"
                            @submit="form.post(route('oracionescantadas.store'))"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
