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
    imagen: null,
});

const handleSubmit = () => {
    form.transform((data) => ({ ...data, _method: 'put' }))
        .post(route('paginas-actividades-online.update', props.pagina.id), { forceFormData: true });
};
</script>

<template>
    <AppLayout title="Editar Pagina Actividades Online">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Editar Conf. Pagina Actividades Online</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link :href="route('paginas-actividades-online.index')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>

                    <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
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

