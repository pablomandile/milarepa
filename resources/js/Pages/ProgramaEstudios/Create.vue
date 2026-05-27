<script>
export default {
    name: 'ProgramaEstudiosCreate',
};
</script>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ProgramaEstudioForm from '@/Components/Formularios/ProgramaEstudioForm.vue';

const form = useForm({
    nombre: '',
    abreviacion: '',
    descripcion: '',
    compromisos_pdf: null,
    compromisos_pdf_borrar: false,
});

function submit() {
    form.post(route('programa-estudios.store'), {
        forceFormData: true,
    });
}
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                Nuevo Programa de Estudio
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
                                :updating="false"
                                :form="form"
                                @submit="submit"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
