<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <InputLabel for="fecha" value="Fecha" />
                <TextInput
                    id="fecha"
                    v-model="form.fecha"
                    type="date"
                    class="mt-1 block w-full"
                    required
                />
                <InputError class="mt-2" :message="form.errors.fecha" />
            </div>

            <div>
                <InputLabel for="user_id" value="Usuario" />
                <TextInput
                    id="user_id"
                    :model-value="authUser?.name ?? 'Usuario actual'"
                    type="text"
                    class="mt-1 block w-full bg-gray-100"
                    disabled
                />
                <InputError class="mt-2" :message="form.errors.user_id" />
            </div>

            <div>
                <InputLabel for="prestadora_id" value="Entidad prestadora" />
                <select
                    id="prestadora_id"
                    v-model="form.prestadora_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    disabled
                    required
                >
                    <option :value="entidadPrestadoraSeleccionada ? entidadPrestadoraSeleccionada.id : null">
                        {{ entidadPrestadoraSeleccionada ? entidadPrestadoraSeleccionada.nombre : 'Entidad principal no configurada' }}
                    </option>
                </select>
                <InputError class="mt-2" :message="form.errors.prestadora_id" />
            </div>

            <div>
                <InputLabel for="receptora_id" value="Entidad receptora" />
                <select
                    id="receptora_id"
                    v-model="form.receptora_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option :value="null">Seleccionar entidad</option>
                    <option v-for="entidad in entidades" :key="entidad.id" :value="entidad.id">
                        {{ entidad.nombre }}
                    </option>
                </select>
                <InputError class="mt-2" :message="form.errors.receptora_id" />
            </div>

            <div>
                <InputLabel for="libro_id" value="Libro" />
                <select
                    id="libro_id"
                    v-model="form.libro_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option :value="null">Seleccionar libro</option>
                    <option v-for="libro in libros" :key="libro.id" :value="libro.id">
                        {{ libro.titulo }}
                    </option>
                </select>
                <p v-if="maxDisponible > 0" class="text-sm text-gray-500 mt-1">
                    Disponible en inventario: {{ maxDisponible }}
                </p>
                <InputError class="mt-2" :message="form.errors.libro_id" />
            </div>

            <div>
                <InputLabel for="cantidad" value="Cantidad" />
                <TextInput
                    id="cantidad"
                    v-model="form.cantidad"
                    type="number"
                    min="1"
                    :max="maxDisponible || undefined"
                    class="mt-1 block w-full"
                    required
                />
                <p v-if="maxDisponible > 0" class="text-sm text-gray-500 mt-1">
                    Máximo permitido: {{ maxDisponible }}
                </p>
                <InputError class="mt-2" :message="form.errors.cantidad" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <SecondaryButton type="button" @click="$inertia.visit(route('inventario-libros.prestamos-anexos.index'))">
                Cancelar
            </SecondaryButton>
            <PrimaryButton :disabled="form.processing">
                {{ submitLabel }}
            </PrimaryButton>
        </div>
    </form>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    prestamo: {
        type: Object,
        default: null,
    },
    entidades: {
        type: Array,
        default: () => [],
    },
    entidadPrincipal: {
        type: Object,
        default: null,
    },
    libros: {
        type: Array,
        default: () => [],
    },
    authUser: {
        type: Object,
        default: null,
    },
    mode: {
        type: String,
        default: 'create',
    },
});

const normalizarFechaInput = (value) => {
    if (!value) {
        return new Date().toISOString().slice(0, 10);
    }

    return String(value).split('T')[0];
};

const form = useForm({
    fecha: normalizarFechaInput(props.prestamo?.fecha),
    prestadora_id: props.entidadPrincipal?.id ?? props.prestamo?.prestadora_id ?? null,
    receptora_id: props.prestamo?.receptora_id ?? null,
    libro_id: props.prestamo?.libro_id ?? null,
    cantidad: props.prestamo?.cantidad ?? 1,
    user_id: props.authUser?.id ?? null,
});

const submitLabel = props.mode === 'edit' ? 'Actualizar' : 'Guardar';

const entidadPrestadoraSeleccionada = computed(() => {
    if (props.entidadPrincipal?.id) {
        return props.entidadPrincipal;
    }

    return props.entidades.find((entidad) => Number(entidad.id) === Number(form.prestadora_id)) || null;
});

watch(
    () => props.entidadPrincipal?.id,
    (principalId) => {
        if (principalId) {
            form.prestadora_id = principalId;
        }
    },
    { immediate: true }
);

const libroSeleccionado = computed(() => {
    return props.libros.find((libro) => Number(libro.id) === Number(form.libro_id)) || null;
});

const maxDisponible = computed(() => {
    return Number(libroSeleccionado.value?.stock_disponible || libroSeleccionado.value?.inventario_libro?.cantidad || 0);
});

watch(
    () => form.libro_id,
    () => {
        if (!maxDisponible.value || Number(form.cantidad) < 1) {
            form.cantidad = 1;
            return;
        }

        if (Number(form.cantidad) > maxDisponible.value) {
            form.cantidad = maxDisponible.value;
        }
    }
);

const submit = () => {
    if (props.mode === 'edit' && props.prestamo) {
        form.put(route('inventario-libros.prestamos-anexos.update', props.prestamo.id));
        return;
    }

    form.post(route('inventario-libros.prestamos-anexos.store'));
};
</script>
