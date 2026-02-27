<script>
export default {
    name: 'OracionesCantadasCreate'
}
</script>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import OracionCantadaForm from '@/Components/Formularios/OracionCantadaForm.vue';

const form = useForm({
    nombre: '',
    descripcion: '',
    dia: 1,
    dias_semana: [],
    hora: '08:00',
    periodicidad: 'Mensual',
    imagen: '',
    mostrar_en_calendario: false,
});
</script>

<template>
    <AppLayout title="Nueva Oración Cantada">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Nueva Oración Cantada</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link :href="route('oracionescantadas.index')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>

                    <div class="p-6 bg-white border-b border-gray-200">
                        <OracionCantadaForm
                            :updating="false"
                            :form="form"
                            @submit="form.post(route('oracionescantadas.store'))"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
