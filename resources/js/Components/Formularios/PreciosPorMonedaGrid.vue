<script>
    export default {
        name: 'PreciosPorMonedaGrid'
    }
</script>

<script setup>
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import TextInput from '../TextInput.vue';
import Dropdown from 'primevue/dropdown';
import { computed } from 'vue';

// Edita form.precios: precios del servicio en monedas NO principales.
// El precio en la moneda principal es el campo de precio normal del form.
const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    monedas: {
        type: Array,
        default: () => []
    },
    botonesPago: {
        type: Array,
        default: () => []
    }
});

const monedaPrincipal = computed(() => props.monedas.find((m) => m.es_principal));
const monedasSecundarias = computed(() => props.monedas.filter((m) => !m.es_principal));

const monedasParaFila = (fila) => {
    const usadas = (props.form.precios || [])
        .filter((p) => p !== fila && p.moneda_id)
        .map((p) => p.moneda_id);
    return monedasSecundarias.value.filter((m) => !usadas.includes(m.id) || m.id === fila.moneda_id);
};

const agregarFila = () => {
    props.form.precios.push({ moneda_id: null, precio: null, botonpago_id: null });
};

const quitarFila = (index) => {
    props.form.precios.splice(index, 1);
};

const errorFila = (index) => {
    const errors = props.form.errors || {};
    return errors[`precios.${index}.moneda_id`] || errors[`precios.${index}.precio`] || errors[`precios.${index}.botonpago_id`];
};
</script>

<template>
    <div v-if="monedasSecundarias.length" class="col-span-6 sm:col-span-6 mb-2">
        <InputLabel value="Precios en otras monedas" />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            El precio de arriba está en {{ monedaPrincipal ? `${monedaPrincipal.nombre} (${monedaPrincipal.simbolo})` : 'la moneda principal' }}.
            Si el servicio no tiene precio en la moneda elegida por la persona, se cobra en {{ monedaPrincipal?.nombre || 'la moneda principal' }}.
        </p>

        <div
            v-for="(fila, index) in form.precios"
            :key="`precio-moneda-${index}`"
            class="mt-2 grid grid-cols-1 sm:grid-cols-12 gap-2 items-start"
        >
            <div class="sm:col-span-4">
                <Dropdown
                    v-model="fila.moneda_id"
                    :options="monedasParaFila(fila)"
                    optionLabel="nombre"
                    optionValue="id"
                    placeholder="Moneda..."
                    class="w-full border border-gray-300 dark:border-gray-600"
                />
            </div>
            <div class="sm:col-span-3">
                <TextInput
                    v-model="fila.precio"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="Precio"
                    class="block w-full"
                />
            </div>
            <div class="sm:col-span-4">
                <Dropdown
                    v-model="fila.botonpago_id"
                    :options="botonesPago"
                    optionLabel="nombre"
                    optionValue="id"
                    placeholder="Botón de pago (opcional)"
                    class="w-full border border-gray-300 dark:border-gray-600"
                    showClear
                />
            </div>
            <div class="sm:col-span-1 flex items-center justify-end sm:justify-center pt-1">
                <button
                    type="button"
                    class="text-red-500 hover:text-red-700"
                    title="Quitar este precio"
                    @click="quitarFila(index)"
                >
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div v-if="errorFila(index)" class="sm:col-span-12">
                <InputError :message="errorFila(index)" />
            </div>
        </div>

        <button
            v-if="form.precios.length < monedasSecundarias.length"
            type="button"
            class="mt-2 text-sm font-semibold text-indigo-600 hover:text-indigo-800"
            @click="agregarFila"
        >
            + Agregar precio en otra moneda
        </button>
        <InputError :message="$page.props.errors.precios" class="mt-2" />
    </div>
</template>
