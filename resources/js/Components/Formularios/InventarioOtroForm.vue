<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <InputLabel for="otro_id" value="Artículo" />
                <select
                    id="otro_id"
                    v-model="form.otro_id"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option :value="null">Seleccionar artículo</option>
                    <option v-for="item in otros" :key="item.id" :value="item.id">
                        {{ item.titulo }}
                    </option>
                </select>
                <InputError class="mt-2" :message="form.errors.otro_id" />
            </div>

            <div>
                <InputLabel for="cantidad" value="Cantidad" />
                <TextInput id="cantidad" v-model="form.cantidad" type="number" min="0" class="mt-1 block w-full" required @focus="onCantidadFocus" />
                <InputError class="mt-2" :message="form.errors.cantidad" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <SecondaryButton type="button" @click="$inertia.visit(route('inventario-otros.index'))">
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
    inventario: {
        type: Object,
        default: null,
    },
    otros: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
    },
});

const form = useForm({
    otro_id: props.inventario?.otro_id ?? null,
    cantidad: props.inventario?.cantidad ?? 0,
});

const submitLabel = props.mode === 'edit' ? 'Actualizar' : 'Guardar';

const onCantidadFocus = () => {
    if (Number(form.cantidad) === 0) {
        form.cantidad = '';
    }
};

const submit = () => {
    if (props.mode === 'edit' && props.inventario) {
        form.put(route('inventario-otros.update', props.inventario.id));
        return;
    }

    form.post(route('inventario-otros.store'));
};
</script>
