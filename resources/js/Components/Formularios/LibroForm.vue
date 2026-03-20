<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <InputLabel for="titulo" value="Titulo" />
                <TextInput id="titulo" v-model="form.titulo" type="text" class="mt-1 block w-full" required />
                <InputError class="mt-2" :message="form.errors.titulo" />
            </div>

            <div class="md:col-span-2">
                <InputLabel for="descripcion" value="Descripcion" />
                <textarea
                    id="descripcion"
                    v-model="form.descripcion"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    rows="4"
                />
                <InputError class="mt-2" :message="form.errors.descripcion" />
            </div>

            <div>
                <InputLabel for="isbn" value="ISBN" />
                <TextInput id="isbn" v-model="form.isbn" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.isbn" />
            </div>

            <div>
                <InputLabel for="autor" value="Autor" />
                <TextInput id="autor" v-model="form.autor" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.autor" />
            </div>

            <div>
                <InputLabel for="editorial" value="Editorial" />
                <TextInput id="editorial" v-model="form.editorial" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.editorial" />
            </div>

            <div>
                <InputLabel for="precio" value="Precio" />
                <TextInput id="precio" v-model="form.precio" type="number" min="0" step="0.01" class="mt-1 block w-full" required />
                <InputError class="mt-2" :message="form.errors.precio" />
            </div>

            <div class="md:col-span-2">
                <InputLabel for="imagen_id" value="Imagen" />
                <select
                    id="imagen_id"
                    v-model="form.imagen_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option :value="null">Sin imagen</option>
                    <option v-for="imagen in imagenes" :key="imagen.id" :value="imagen.id">
                        {{ imagen.nombre }}
                    </option>
                </select>
                <InputError class="mt-2" :message="form.errors.imagen_id" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <SecondaryButton type="button" @click="$inertia.visit(route('libros.index'))">
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
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    libro: {
        type: Object,
        default: null,
    },
    imagenes: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
    },
});

const form = useForm({
    titulo: props.libro?.titulo ?? '',
    descripcion: props.libro?.descripcion ?? '',
    isbn: props.libro?.isbn ?? '',
    autor: props.libro?.autor ?? '',
    editorial: props.libro?.editorial ?? '',
    imagen_id: props.libro?.imagen_id ?? null,
    precio: props.libro?.precio ?? 0,
});

const submitLabel = props.mode === 'edit' ? 'Actualizar' : 'Guardar';

const submit = () => {
    if (props.mode === 'edit' && props.libro) {
        form.put(route('libros.update', props.libro.id));
        return;
    }

    form.post(route('libros.store'));
};
</script>
