<script>
export default {
    name: 'ProgramaEstudiosEdit',
};
</script>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProgramaEstudioForm from '@/Components/Formularios/ProgramaEstudioForm.vue';

const props = defineProps({
    programaEstudio: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    _method: 'PUT',
    nombre: props.programaEstudio.nombre,
    abreviacion: props.programaEstudio.abreviacion || '',
    descripcion: props.programaEstudio.descripcion || '',
    compromisos_pdf: null,
    compromisos_pdf_borrar: false,
});

const compromisosPdfUrl = props.programaEstudio.compromisos_pdf
    ? `/storage/${props.programaEstudio.compromisos_pdf}`
    : '';

const compromisosPdfNombre = props.programaEstudio.compromisos_pdf
    ? props.programaEstudio.compromisos_pdf.split('/').pop()
    : '';

const handleSubmit = () => {
    form.post(route('programa-estudios.update', props.programaEstudio.id), {
        forceFormData: true,
    });
};
</script>

<template>
    <AppLayout title="Editar Programa de Estudio">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                Editar Programa de Estudio
            </h1>
        </template>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link
                            :href="route('programa-estudios.index')"
                            class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded"
                        >
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <ProgramaEstudioForm
                                :updating="true"
                                :form="form"
                                :compromisos-pdf-url="compromisosPdfUrl"
                                :compromisos-pdf-nombre="compromisosPdfNombre"
                                @submit="handleSubmit"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
