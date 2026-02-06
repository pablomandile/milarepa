<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
  actividad: {
    type: Object,
    required: true,
  },
  pago: {
    type: Object,
    required: true,
  },
  saldo: {
    type: [Number, String],
    default: 0,
  },
  membresia: {
    type: String,
    default: 'Sin membresía',
  },
});

const toast = useToast();
const comprobanteModal = ref(false);
const comprobanteFile = ref(null);
const isUploading = ref(false);
const isFinalizing = ref(false);
const pagoMetodo = ref(props.pago?.pago_metodo || null);
const comprobantePath = ref(props.pago?.comprobante_path || null);

const saldoFinal = computed(() => parseFloat(props.saldo || 0));

const puedeFinalizar = computed(() => {
  return pagoMetodo.value === 'efectivo' || pagoMetodo.value === 'transferencia' || !!comprobantePath.value;
});

const descripcionEfectivo = computed(() => {
  const metodo = props.actividad.metodos_pago?.find(
    (m) => m.nombre?.toLowerCase() === 'efectivo'
  );
  return metodo?.descripcion || 'Registrá el pago en efectivo. La inscripción quedará en estado pendiente para aprobación.';
});

const descripcionTransferencia = computed(() => {
  const metodo = props.actividad.metodos_pago?.find(
    (m) => m.nombre?.toLowerCase() === 'transferencia'
  );
  return metodo?.descripcion || 'Subí un comprobante (PDF o imagen) para registrar el pago.';
});

const tieneEfectivo = computed(() =>
  props.actividad.metodos_pago?.some((m) => m.nombre?.toLowerCase() === 'efectivo')
);
const tieneTransferencia = computed(() =>
  props.actividad.metodos_pago?.some((m) => m.nombre?.toLowerCase() === 'transferencia')
);

function seleccionarComprobante(event) {
  comprobanteFile.value = event.target.files?.[0] || null;
}

async function subirComprobante() {
  if (!comprobanteFile.value) return;
  isUploading.value = true;
  try {
    const data = new FormData();
    data.append('comprobante', comprobanteFile.value);
    const response = await axios.post(route('grid-actividades.pago.comprobante'), data);
    comprobantePath.value = response.data.path;
    pagoMetodo.value = 'comprobante';
    comprobanteModal.value = false;
    toast.add({
      severity: 'success',
      summary: 'Comprobante',
      detail: 'Comprobante subido correctamente.',
      life: 4000,
    });
  } catch (error) {
    const mensaje = error?.response?.data?.message || error?.response?.data?.errors?.comprobante?.[0] || 'No se pudo subir el comprobante.';
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: mensaje,
      life: 5000,
    });
  } finally {
    isUploading.value = false;
  }
}

async function terminar() {
  if (!puedeFinalizar.value) return;
  isFinalizing.value = true;
  try {
    const response = await axios.post(route('grid-actividades.pago.finalizar'), {
      pago_metodo: pagoMetodo.value || 'efectivo',
    });
    const inscripcionId = response.data?.inscripcion_id;
    const registrado = response.data?.registered;
    if (inscripcionId) {
      if (registrado) {
        window.location.href = route('inscripciones.show', { inscripcion: inscripcionId });
      } else {
        window.location.href = route('grid-actividades.inscripcion', { inscripcion: inscripcionId });
      }
      return;
    }
    toast.add({
      severity: 'success',
      summary: 'Inscripción',
      detail: 'Inscripción registrada correctamente.',
      life: 5000,
    });
  } catch (error) {
    const mensaje = error?.response?.data?.message || 'No se pudo finalizar la inscripción.';
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: mensaje,
      life: 5000,
    });
  } finally {
    isFinalizing.value = false;
  }
}
</script>

<template>
  <AppLayout>
    <template #header>
      <h1 class="font-semibold text-xl text-gray-800 leading-tight">Pago de inscripción</h1>
    </template>
    <Toast position="top-right" />
    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white border border-gray-200 rounded-lg p-6">
          <h2 class="text-lg font-semibold text-gray-800">
            {{ actividad.nombre }}
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Saldo a pagar: <span class="font-semibold text-gray-800">$ {{ saldoFinal.toLocaleString('es-AR') }}</span>
          </p>
          <p class="text-xs text-gray-500 mt-1">
            Membresía aplicada: {{ membresia }}
          </p>

          <div class="mt-4">
            <p class="text-sm text-gray-700 mb-2">Medios de pago disponibles:</p>
            <div class="flex flex-wrap gap-2">
              <Tag
                v-for="metodo in actividad.metodos_pago"
                :key="metodo.id"
                severity="info"
                :value="metodo.nombre"
              />
            </div>
          </div>

          <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-if="tieneEfectivo" class="border rounded-lg p-4">
              <h3 class="text-sm font-semibold text-gray-700">Pago en efectivo</h3>
              <p class="text-xs text-gray-500 mt-1">
                {{ descripcionEfectivo }} Tu inscripción quedará en estado pendiente para aprobación.
              </p>
              <button
                class="mt-3 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700"
                @click="pagoMetodo = 'efectivo'"
              >
                Pagaré en efectivo más tarde
              </button>
            </div>
            <div v-if="tieneTransferencia" class="border rounded-lg p-4">
              <h3 class="text-sm font-semibold text-gray-700">Pagar por transferencia</h3>
              <p class="text-xs text-gray-500 mt-1">
                {{ descripcionTransferencia }}
              </p>
              <div class="mt-3 flex flex-wrap gap-2">
                <button
                  class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
                  @click="comprobanteModal = true"
                >
                  Subir comprobante
                </button>
                <button
                  class="px-4 py-2 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200"
                  @click="pagoMetodo = 'transferencia'"
                >
                  Transferiré más tarde
                </button>
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end">
            <button
              class="px-5 py-2 rounded text-white bg-green-600 hover:bg-green-700 disabled:opacity-60"
              :disabled="!puedeFinalizar || isFinalizing"
              @click="terminar"
            >
              {{ isFinalizing ? 'Finalizando...' : 'Terminar' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <Dialog
      v-model:visible="comprobanteModal"
      modal
      header="Subir comprobante"
      :style="{ width: '500px' }"
    >
      <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="seleccionarComprobante" />
      <template #footer>
        <div class="flex justify-end gap-2">
          <button class="px-4 py-2 bg-gray-500 text-white rounded" @click="comprobanteModal = false">
            Cancelar
          </button>
          <button
            class="px-4 py-2 bg-indigo-600 text-white rounded disabled:opacity-60"
            :disabled="isUploading || !comprobanteFile"
            @click="subirComprobante"
          >
            {{ isUploading ? 'Subiendo...' : 'Subir' }}
          </button>
        </div>
      </template>
    </Dialog>
  </AppLayout>
</template>
