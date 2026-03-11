<script>
export default {
    name: 'PaginasActividadesOnlineCreate'
}
</script>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PaginaActividadOnlineForm from '@/Components/Formularios/PaginaActividadOnlineForm.vue';

const form = useForm({
    mes_referencia: '',
    imagen_id: null,
});
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Nueva Conf. Pagina Actividades Online</h1>
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
                            :updating="false"
                            :form="form"
                            :imagen-preview-url="''"
                            @submit="form.post(route('paginas-actividades-online.store'))"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

