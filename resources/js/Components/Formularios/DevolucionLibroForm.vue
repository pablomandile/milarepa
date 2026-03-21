<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <InputLabel for="fecha" value="Fecha" />
                <TextInput id="fecha" v-model="form.fecha" type="date" class="mt-1 block w-full" required />
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
            </div>

            <div>
                <InputLabel for="devolvedor_id" value="Entidad devolvedora" />
                <select
                    id="devolvedor_id"
                    v-model="form.devolvedor_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option :value="null">Seleccionar entidad</option>
                    <option v-for="entidad in entidadesNoPrincipal" :key="entidad.id" :value="entidad.id">
                        {{ entidad.nombre }}
                    </option>
                </select>
                <InputError class="mt-2" :message="form.errors.devolvedor_id" />
            </div>

            <div>
                <InputLabel for="prestador_id" value="Entidad prestadora" />
                <select
                    id="prestador_id"
                    v-model="form.prestador_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    disabled
                    required
                >
                    <option :value="entidadPrestadoraSeleccionada?.id ?? null">
                        {{ entidadPrestadoraSeleccionada?.nombre ?? 'Entidad principal no configurada' }}
                    </option>
                </select>
                <InputError class="mt-2" :message="form.errors.prestador_id" />
            </div>

            <div>
                <InputLabel for="libro_id" value="Libro" />
                <select
                    id="libro_id"
                    v-model="form.libro_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    :disabled="!form.devolvedor_id || cargandoLibros"
                    required
                >
                    <option :value="null">{{ form.devolvedor_id ? 'Seleccionar libro' : 'Seleccione devolvedor primero' }}</option>
                    <option v-for="libro in librosDisponibles" :key="libro.id" :value="libro.id">
                        {{ libro.titulo }}
                    </option>
                </select>
                <p v-if="maxDisponible > 0" class="text-sm text-gray-500 mt-1">Disponible en entidad: {{ maxDisponible }}</p>
                <InputError class="mt-2" :message="form.errors.libro_id" />
            </div>

            <div>
                <InputLabel for="cantidad" value="Cantidad" />
                <TextInput id="cantidad" v-model="form.cantidad" type="number" min="1" :max="maxDisponible || undefined" class="mt-1 block w-full" required />
                <p v-if="maxDisponible > 0" class="text-sm text-gray-500 mt-1">Máximo permitido: {{ maxDisponible }}</p>
                <InputError class="mt-2" :message="form.errors.cantidad" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <SecondaryButton type="button" @click="$inertia.visit(route('inventario-libros.devoluciones-anexos.index'))">Cancelar</SecondaryButton>
            <PrimaryButton :disabled="form.processing">Guardar</PrimaryButton>
        </div>
    </form>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    entidades: { type: Array, default: () => [] },
    entidadPrincipal: { type: Object, default: null },
    libros: { type: Array, default: () => [] },
    authUser: { type: Object, default: null },
});

const cargandoLibros = ref(false);
const librosDisponibles = ref([...(props.libros || [])]);

const form = useForm({
    fecha: new Date().toISOString().slice(0, 10),
    devolvedor_id: null,
    prestador_id: props.entidadPrincipal?.id ?? null,
    libro_id: null,
    cantidad: 1,
    user_id: props.authUser?.id ?? null,
});

const entidadesNoPrincipal = computed(() => {
    const principalId = Number(props.entidadPrincipal?.id || 0);
    return (props.entidades || []).filter((entidad) => Number(entidad.id) !== principalId);
});

const entidadPrestadoraSeleccionada = computed(() => props.entidadPrincipal || null);

const libroSeleccionado = computed(() => {
    return librosDisponibles.value.find((libro) => Number(libro.id) === Number(form.libro_id)) || null;
});

const maxDisponible = computed(() => Number(libroSeleccionado.value?.stock_disponible || 0));

watch(
    () => form.devolvedor_id,
    async (devolvedorId) => {
        form.libro_id = null;
        form.cantidad = 1;

        if (!devolvedorId) {
            librosDisponibles.value = [...(props.libros || [])];
            return;
        }

        cargandoLibros.value = true;

        try {
            const response = await fetch(`${route('inventario-libros.devoluciones-anexos.libros-por-entidad')}?entidad_id=${Number(devolvedorId)}`);
            const data = await response.json();
            librosDisponibles.value = Array.isArray(data.libros) ? data.libros : [];
        } catch (error) {
            librosDisponibles.value = [];
        } finally {
            cargandoLibros.value = false;
        }
    },
    { immediate: true }
);

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
    form.post(route('inventario-libros.devoluciones-anexos.store'));
};
</script>
