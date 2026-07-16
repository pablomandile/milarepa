<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <InputLabel for="nombre" value="Nombre" />
                <TextInput id="nombre" v-model="form.nombre" type="text" class="mt-1 block w-full" required />
                <InputError class="mt-2" :message="form.errors.nombre" />
            </div>

            <div>
                <InputLabel for="orden" value="Orden (opcional)" />
                <TextInput id="orden" v-model="form.orden" type="number" min="0" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.orden" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <SecondaryButton type="button" @click="$inertia.visit(route('categorias-tienda.index'))">
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
    categoria: {
        type: Object,
        default: null,
    },
    mode: {
        type: String,
        default: 'create',
    },
});

const form = useForm({
    nombre: props.categoria?.nombre ?? '',
    orden: props.categoria?.orden ?? null,
});

const submitLabel = props.mode === 'edit' ? 'Actualizar' : 'Guardar';

const submit = () => {
    if (props.mode === 'edit' && props.categoria) {
        form.put(route('categorias-tienda.update', props.categoria.id));
        return;
    }

    form.post(route('categorias-tienda.store'));
};
</script>
