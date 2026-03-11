<script>
export default {
    name: 'PaginasActividadesOnlineEdit'
}
</script>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PaginaActividadOnlineForm from '@/Components/Formularios/PaginaActividadOnlineForm.vue';

const props = defineProps({
    pagina: {
        type: Object,
        required: true
    }
});

const form = useForm({
    mes_referencia: props.pagina.mes_referencia || '',
    imagen_id: props.pagina.imagen_id || null,
});

const handleSubmit = () => {
    form.put(route('paginas-actividades-online.update', props.pagina.id));
};
</script>

<template>
    <AppLayout title="Editar Pagina Actividades Online">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Editar Conf. Pagina Actividades Online</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link :href="route('paginas-actividades-online.index')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>

                    <div class="p-6 bg-white border-b border-gray-200">
                        <PaginaActividadOnlineForm
                            :updating="true"
                            :form="form"
                            :imagen-preview-url="props.pagina.imagen ? `/storage/${props.pagina.imagen.ruta}` : ''"
                            @submit="handleSubmit"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

