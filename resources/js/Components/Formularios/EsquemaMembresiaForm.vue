<script setup>
import FormSection from '@/Components/FormSection.vue';
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import Dropdown from 'primevue/dropdown';
import { computed, defineProps, defineEmits } from 'vue';

const props = defineProps({
  form: {
    type: Object,
    required: true
  },
  membresias: {
    type: Array,
    default: () => []
  },
  monedas: {
    type: Array,
    default: () => []
  },
  botonesPago: {
    type: Array,
    default: () => []
  },
  // Habilita "Gratis con TK": true cuando el esquema ya tiene al menos un precio cargado.
  tienePrecios: {
    type: Boolean,
    default: false
  },
  // Muestra los botones de acciones masivas ("Todos iguales" y "Gratis con TK").
  // Solo las pantallas que las tienen conectadas (Precios, Descuentos) deben activarlas.
  accionesMasivas: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['submit', 'submit-iguales', 'submit-gratis']);

const botonesPagoOrdenados = computed(() =>
  [...props.botonesPago].sort((a, b) => Number(b.id) - Number(a.id))
);

// Habilita "Todos iguales" solo cuando hay membresía, moneda e importe cargados.
const puedeIguales = computed(() =>
  !!props.form.membresia_id &&
  !!props.form.moneda_id &&
  props.form.precio !== null &&
  props.form.precio !== undefined &&
  props.form.precio !== ''
);

function handleSubmit() {
  emit('submit');
}
</script>

<template>
  <FormSection @submitted="handleSubmit">
    <template #title>
        <div class="max-w-sm">
            Añadir Membresía al Esquema
      </div>
    </template>

    <template #description>
        <div class="max-w-sm">
            Agrega una nueva membresía con precio y moneda
      </div>
    </template>

    <template #form>
            <div class="col-span-6 sm:col-span-2 mb-2">
                <InputLabel for="membresia_id" value="Membresía" :required="true"/>
                <Dropdown
                    id="membresia_id"
                    v-model="form.membresia_id"
                    :options="membresias"
                    optionLabel="label"
                    optionValue="id"
                    placeholder="Membresía..."
                    class="w-full mt-1 border border-gray-300 dark:border-gray-600"
                />
                <InputError :message="$page.props.errors.membresia_id" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-2 mb-2">
                <InputLabel for="moneda_id" value="Moneda" :required="true"/>
                <Dropdown
                    id="moneda_id"
                    v-model="form.moneda_id"
                    :options="monedas"
                    optionLabel="nombre"
                    optionValue="id"
                    placeholder="Moneda..."
                    class="w-full mt-1 border border-gray-300 dark:border-gray-600"
                />
                <InputError :message="$page.props.errors.moneda_id" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-2 mb-2">
                <InputLabel for="botonpago_id" value="Boton de Pago" :required="false"/>
                <Dropdown
                    id="botonpago_id"
                    v-model="form.botonpago_id"
                    :options="botonesPagoOrdenados"
                    optionLabel="nombre"
                    optionValue="id"
                    placeholder="Seleccione un boton de pago"
                    class="w-full mt-1 border border-gray-300 dark:border-gray-600"
                    showClear
                />
                <InputError :message="$page.props.errors.botonpago_id" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-2 mb-2">
                <InputLabel for="precio" value="Precio" :required="true"/>
                <TextInput
                    id="precio"
                    v-model="form.precio"
                    type="number"
                    step="1"
                    min="0"
                    placeholder="$0"
                    class="mt-2 block w-full"
                />
                <InputError :message="$page.props.errors.precio" class="mt-2" />
            </div>
    </template>

    <template #actions>
      <div class="flex items-center gap-3">
        <PrimaryButton>
          Agregar
        </PrimaryButton>
        <template v-if="accionesMasivas">
        <button
          type="button"
          :disabled="!puedeIguales"
          @click="$emit('submit-iguales')"
          v-tooltip.top="'Aplica esta moneda, importe y botón de pago a todas las membresías de la misma entidad que aún no tengan precio'"
          class="inline-flex items-center gap-2 rounded-md bg-indigo-100 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-200 disabled:opacity-40 disabled:cursor-not-allowed dark:bg-indigo-500/20 dark:text-indigo-300 dark:hover:bg-indigo-500/30"
        >
          <i class="pi pi-clone"></i>
          Todos iguales
        </button>
        <button
          type="button"
          :disabled="!tienePrecios"
          @click="$emit('submit-gratis')"
          v-tooltip.top="'Pone en $0 las membresías que falten de las entidades que ya tienen precio (gratis con ticket)'"
          class="inline-flex items-center gap-2 rounded-md bg-emerald-100 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-200 disabled:opacity-40 disabled:cursor-not-allowed dark:bg-emerald-500/20 dark:text-emerald-300 dark:hover:bg-emerald-500/30"
        >
          <i class="pi pi-ticket"></i>
          Gratis con TK
        </button>
        </template>
      </div>
    </template>
  </FormSection>
</template>
