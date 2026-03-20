<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SingleImageUploader from '@/Components/SingleImageUploader.vue';
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    imagenId: {
        type: Number,
        default: null,
    },
    imagenUrl: {
        type: String,
        default: '',
    },
});

const form = useForm({
    imagen_id: props.imagenId,
});

const imagenActual = computed(() => {
    if (form.imagen_id && props.imagenUrl) {
        return props.imagenUrl;
    }

    return '';
});

const guardar = () => {
    form.put(route('mail-info-membresias.update'), {
        preserveScroll: true,
    });
};

const quitarImagen = () => {
    form.imagen_id = null;
};
</script>

<template>
    <AppLayout title="Mail Info Membresías">
        <template #header>
            <h1 class="font-semibold text-xl text-gray-800 leading-tight">Mail Info Membresías</h1>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Imagen para email "Info de Tarjetas Kadampa"</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Subí una imagen y luego guardá la selección para usarla en la plantilla.
                            El archivo se almacena en storage/img/membresias.
                        </p>
                    </div>

                    <SingleImageUploader
                        v-model:imagenId="form.imagen_id"
                        folder="img/membresias"
                    />

                    <div v-if="imagenActual" class="border border-gray-200 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-800 mb-3">Imagen actualmente guardada</p>
                        <img
                            :src="imagenActual"
                            alt="Imagen guardada para Mail Info Membresías"
                            class="max-h-56 rounded border border-gray-200 object-contain"
                        />
                    </div>

                    <div class="flex flex-wrap gap-3 justify-end">
                        <button
                            type="button"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 disabled:opacity-50"
                            :disabled="form.processing"
                            @click="quitarImagen"
                        >
                            Quitar imagen
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50"
                            :disabled="form.processing"
                            @click="guardar"
                        >
                            {{ form.processing ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>

                    <p v-if="form.errors.imagen_id" class="text-sm text-red-600">
                        {{ form.errors.imagen_id }}
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
