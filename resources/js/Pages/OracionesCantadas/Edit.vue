<script>
export default {
    name: 'OracionesCantadasEdit'
}
</script>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import OracionCantadaForm from '@/Components/Formularios/OracionCantadaForm.vue';

const props = defineProps({
    oracionCantada: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    nombre: props.oracionCantada.nombre ?? '',
    descripcion: props.oracionCantada.descripcion ?? '',
    dia: props.oracionCantada.dia ?? 1,
    dias_semana: Array.isArray(props.oracionCantada.dias_semana) ? props.oracionCantada.dias_semana : [],
    hora: props.oracionCantada.hora ? String(props.oracionCantada.hora).slice(0, 5) : '08:00',
    periodicidad: props.oracionCantada.periodicidad ?? 'Mensual',
    imagen: props.oracionCantada.imagen ?? '',
    mostrar_en_calendario: !!props.oracionCantada.mostrar_en_calendario,
});

const handleSubmit = () => {
    form.put(route('oracionescantadas.update', props.oracionCantada.id));
};
</script>

<template>
    <AppLayout title="Editar Oración Cantada">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Editar Oración Cantada</h1>
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
                            :updating="true"
                            :form="form"
                            @submit="handleSubmit"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
