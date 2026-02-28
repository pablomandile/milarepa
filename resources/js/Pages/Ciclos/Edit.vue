<script>
export default {
    name: 'CiclosEdit'
}
</script>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CicloForm from '@/Components/Formularios/CicloForm.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    ciclo: {
        type: Object,
        required: true
    }
});

const form = useForm({
    nombre: props.ciclo.nombre
});

const handleSubmit = () => {
    form.put(route('ciclos.update', props.ciclo.id));
};
</script>

<template>
    <AppLayout title="Editar Ciclo">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Editar Ciclo</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link :href="route('ciclos.index')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                        <div class="p-6 bg-white border-b border-gray-200">
                            <CicloForm :updating="true" :form="form" @submit="handleSubmit" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

