<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <InputLabel for="oracion_id" value="Oración" />
                <select
                    id="oracion_id"
                    v-model="form.oracion_id"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option :value="null">Seleccionar oración</option>
                    <option v-for="oracion in oraciones" :key="oracion.id" :value="oracion.id">
                        {{ oracion.titulo }}
                    </option>
                </select>
                <InputError class="mt-2" :message="form.errors.oracion_id" />
            </div>

            <div>
                <InputLabel for="cantidad" value="Cantidad" />
                <TextInput id="cantidad" v-model="form.cantidad" type="number" min="0" class="mt-1 block w-full" required @focus="onCantidadFocus" />
                <InputError class="mt-2" :message="form.errors.cantidad" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <SecondaryButton type="button" @click="$inertia.visit(route('inventario-oraciones.index'))">
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
    oraciones: {
        type: Array,
        default: () => [],
    },
    mode: {
        type: String,
        default: 'create',
    },
});

const form = useForm({
    oracion_id: props.inventario?.oracion_id ?? null,
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
        form.put(route('inventario-oraciones.update', props.inventario.id));
        return;
    }

    form.post(route('inventario-oraciones.store'));
};
</script>
