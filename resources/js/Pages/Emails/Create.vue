<script>
export default {
    name: 'EmailsCreate'
}
</script>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dialog from 'primevue/dialog';

const props = defineProps({
    templateFiles: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    nombre: '',
    descripcion: '',
    plantilla_archivo: '',
});

const showTemplates = ref(false);

const selectTemplate = (file) => {
    form.plantilla_archivo = file;
    showTemplates.value = false;
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo Email</h1>
        </template>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-soft-indigo sm:rounded-lg">
                    <div class="flex justify-end mr-5 mb-6 mt-3">
                        <Link :href="route('emails.index')" class="text-white bg-indigo-500 hover:bg-indigo-700 py-2 px-4 rounded">
                            Volver
                        </Link>
                    </div>
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="form.post(route('emails.store'))" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nombre</label>
                                <input
                                    v-model="form.nombre"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="form.errors.nombre" class="text-sm text-red-600 mt-1">{{ form.errors.nombre }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Descripcion</label>
                                <textarea
                                    v-model="form.descripcion"
                                    rows="4"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="form.errors.descripcion" class="text-sm text-red-600 mt-1">{{ form.errors.descripcion }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Plantilla (views/emails)</label>
                                <div class="mt-1 flex gap-2">
                                    <select
                                        v-model="form.plantilla_archivo"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">Seleccionar plantilla</option>
                                        <option v-for="file in props.templateFiles" :key="file" :value="file">
                                            {{ file }}
                                        </option>
                                    </select>
                                    <button
                                        type="button"
                                        class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300"
                                        @click="showTemplates = true"
                                    >
                                        Explorar
                                    </button>
                                </div>
                                <p v-if="form.errors.plantilla_archivo" class="text-sm text-red-600 mt-1">{{ form.errors.plantilla_archivo }}</p>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    class="text-white bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded"
                                    :disabled="form.processing"
                                >
                                    Guardar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="showTemplates" modal header="Plantillas disponibles" :style="{ width: '520px' }">
            <div class="space-y-2">
                <button
                    v-for="file in props.templateFiles"
                    :key="file"
                    type="button"
                    class="w-full text-left px-3 py-2 rounded border border-gray-200 hover:bg-gray-50"
                    @click="selectTemplate(file)"
                >
                    {{ file }}
                </button>
                <p v-if="props.templateFiles.length === 0" class="text-sm text-gray-500">No hay plantillas disponibles.</p>
            </div>
        </Dialog>
    </AppLayout>
</template>
