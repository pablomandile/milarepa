<template>
    <form @submit.prevent="submit" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <InputLabel for="fecha" value="Fecha" />
                <TextInput id="fecha" v-model="form.fecha" type="date" class="mt-1 block w-full" required />
                <InputError class="mt-2" :message="form.errors.fecha" />
            </div>

            <div>
                <InputLabel for="entidad_id" value="Entidad" />
                <select
                    id="entidad_id"
                    v-model="form.entidad_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option :value="null">Seleccionar entidad</option>
                    <option v-for="entidad in entidades" :key="entidad.id" :value="entidad.id">{{ entidad.nombre }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.entidad_id" />
            </div>

            <div>
                <InputLabel for="libro_id" value="Libro" />
                <select
                    id="libro_id"
                    v-model="form.libro_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    :disabled="!form.entidad_id || cargandoLibros"
                    required
                >
                    <option :value="null">{{ form.entidad_id ? 'Seleccionar libro' : 'Seleccione entidad primero' }}</option>
                    <option v-for="libro in librosDisponibles" :key="libro.id" :value="libro.id">{{ libro.titulo }}</option>
                </select>
                <p v-if="stockDisponible > 0" class="text-xs text-gray-500 mt-1">Stock disponible: {{ stockDisponible }}</p>
                <InputError class="mt-2" :message="form.errors.libro_id" />
            </div>

            <div>
                <InputLabel for="precio_unitario" value="Precio unitario" />
                <TextInput id="precio_unitario" v-model="form.precio_unitario" type="number" min="0" step="0.01" class="mt-1 block w-full" required @focus="onPrecioFocus" />
                <InputError class="mt-2" :message="form.errors.precio_unitario" />
            </div>

            <div>
                <InputLabel for="cantidad" value="Cantidad" />
                <TextInput id="cantidad" v-model="form.cantidad" type="number" min="1" :max="stockDisponible || undefined" class="mt-1 block w-full" required />
                <InputError class="mt-2" :message="form.errors.cantidad" />
            </div>

            <div>
                <InputLabel for="montoTotal" value="Monto total" />
                <TextInput id="montoTotal" :model-value="montoTotalCalculado" type="text" class="mt-1 block w-full bg-gray-100" readonly />
                <InputError class="mt-2" :message="form.errors.montoTotal" />
            </div>

            <div>
                <InputLabel for="modo" value="Modo de pago" />
                <select
                    id="modo"
                    v-model="form.modo"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option :value="null">Seleccionar modo</option>
                    <option v-for="modo in modosPago" :key="modo" :value="modo">{{ modo }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.modo" />
            </div>

            <div>
                <InputLabel value="Comprobante" />
                <SingleImageUploader
                    v-model:imagenId="form.comprobante_id"
                    folder="img/mpago"
                />
                <InputError class="mt-2" :message="form.errors.comprobante_id" />
            </div>
        </div>

        <div class="flex justify-end">
            <PrimaryButton :disabled="form.processing">Registrar</PrimaryButton>
        </div>
    </form>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SingleImageUploader from '@/Components/SingleImageUploader.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    entidades: {
        type: Array,
        default: () => [],
    },
    modosPago: {
        type: Array,
        default: () => [],
    },
});

const librosDisponibles = ref([]);
const cargandoLibros = ref(false);

const form = useForm({
    fecha: new Date().toISOString().slice(0, 10),
    entidad_id: null,
    libro_id: null,
    precio_unitario: 0,
    cantidad: 1,
    montoTotal: 0,
    modo: null,
    comprobante_id: null,
});

const libroSeleccionado = computed(() => {
    return librosDisponibles.value.find((libro) => Number(libro.id) === Number(form.libro_id)) || null;
});

const stockDisponible = computed(() => Number(libroSeleccionado.value?.stock_disponible || 0));

const montoTotalCalculado = computed(() => {
    const total = Number(form.precio_unitario || 0) * Number(form.cantidad || 0);
    return total.toFixed(2);
});

watch(
    () => [form.precio_unitario, form.cantidad],
    () => {
        form.montoTotal = Number(montoTotalCalculado.value);
    },
    { immediate: true }
);

watch(
    () => form.entidad_id,
    async (entidadId) => {
        form.libro_id = null;
        form.cantidad = 1;
        form.precio_unitario = 0;

        if (!entidadId) {
            librosDisponibles.value = [];
            return;
        }

        cargandoLibros.value = true;

        try {
            const response = await fetch(`${route('inventario-libros.ventas.libros-por-entidad')}?entidad_id=${Number(entidadId)}`);
            const data = await response.json();
            librosDisponibles.value = Array.isArray(data.libros) ? data.libros : [];
        } catch (error) {
            librosDisponibles.value = [];
        } finally {
            cargandoLibros.value = false;
        }
    }
);

watch(
    () => form.libro_id,
    () => {
        if (!form.libro_id) {
            form.precio_unitario = 0;
            form.cantidad = 1;
            return;
        }

        form.precio_unitario = Number(libroSeleccionado.value?.precio_unitario || 0);

        if (Number(form.cantidad) > stockDisponible.value) {
            form.cantidad = stockDisponible.value || 1;
        }
    }
);

const onPrecioFocus = () => {
    if (Number(form.precio_unitario) === 0) {
        form.precio_unitario = '';
    }
};

const submit = () => {
    form.montoTotal = Number(montoTotalCalculado.value);
    form.post(route('inventario-libros.ventas.store'), {
        preserveScroll: true,
    });
};
</script>
