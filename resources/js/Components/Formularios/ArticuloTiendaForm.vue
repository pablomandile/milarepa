<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div>
            <InputLabel for="titulo" value="Titulo" />
            <TextInput id="titulo" v-model="form.titulo" type="text" class="mt-1 block w-full" required />
            <InputError class="mt-2" :message="form.errors.titulo" />
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <InputLabel for="descripcion" value="Descripcion" />
                <textarea
                    id="descripcion"
                    v-model="form.descripcion"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    rows="4"
                />
                <InputError class="mt-2" :message="form.errors.descripcion" />
            </div>

            <div>
                <InputLabel for="categoria_tienda_id" value="Categoría" />
                <select
                    id="categoria_tienda_id"
                    v-model="form.categoria_tienda_id"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option :value="null">Seleccionar categoría</option>
                    <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.categoria_tienda_id" />
                <p v-if="!categorias.length" class="mt-1 text-xs text-amber-600">
                    No hay categorías cargadas. Creá una en Tienda → Categorías.
                </p>
            </div>

            <div>
                <InputLabel for="precio" value="Precio" />
                <TextInput id="precio" v-model="form.precio" type="number" min="0" step="0.01" class="mt-1 block w-full" required @focus="onPrecioFocus" />
                <InputError class="mt-2" :message="form.errors.precio" />
            </div>

            <div class="md:col-span-2">
                <InputLabel for="imagen_id" value="Imagen" />
                <div class="flex items-start gap-4">
                    <SingleImageUploader
                        v-model:file="form.imagen"
                        folder="img/tienda"
                    />
                    <div v-if="imagenPreviewUrl" class="flex items-center gap-2">
                        <div class="h-16 w-16 rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center justify-center overflow-hidden">
                            <img
                                :src="imagenPreviewUrl"
                                alt="Imagen actual"
                                class="max-h-full max-w-full object-contain"
                            />
                        </div>
                        <span class="text-sm text-gray-500">Actual</span>
                    </div>
                </div>
                <InputError class="mt-2" :message="form.errors.imagen_id" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <SecondaryButton type="button" @click="$inertia.visit(route('articulos-tienda.index'))">
                Cancelar
            </SecondaryButton>
            <PrimaryButton :disabled="form.processing">
                {{ submitLabel }}
            </PrimaryButton>
        </div>
    </form>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SingleImageUploader from '@/Components/SingleImageUploader.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    articulo: {
        type: Object,
        default: null,
    },
    categorias: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
    },
});

const form = useForm({
    titulo: props.articulo?.titulo ?? '',
    descripcion: props.articulo?.descripcion ?? '',
    categoria_tienda_id: props.articulo?.categoria_tienda_id ?? null,
    imagen_id: props.articulo?.imagen_id ?? null,
    imagen: null,
    precio: props.articulo?.precio ?? 0,
});

const submitLabel = props.mode === 'edit' ? 'Actualizar' : 'Guardar';

const imagenPreviewUrl = props.articulo?.imagen?.ruta ? `/storage/${props.articulo.imagen.ruta}` : '';

const onPrecioFocus = () => {
    if (Number(form.precio) === 0) {
        form.precio = '';
    }
};

const submit = () => {
    if (props.mode === 'edit' && props.articulo) {
        form.transform((data) => ({ ...data, _method: 'put' }))
            .post(route('articulos-tienda.update', props.articulo.id), { forceFormData: true });
        return;
    }

    form.post(route('articulos-tienda.store'));
};
</script>
