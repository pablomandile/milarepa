<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Checkbox from 'primevue/checkbox';
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
const actividadPagoLink = computed(() => props.actividad?.boton_pago?.link || '');
const grabacionSeleccionada = ref(false);
const comidasSeleccionadas = ref([]);
const transportesSeleccionados = ref([]);
const hospedajesSeleccionados = ref([]);

const actividadPrecio = computed(() => parseFloat(props.saldo || 0) || 0);
const grabacionDisponible = computed(() => !!props.actividad?.grabacion_id && !!props.actividad?.grabacion);
const grabacionPrecio = computed(() => {
  const valor = props.actividad?.grabacion?.valor;
  return valor ? parseFloat(valor) : 0;
});
const grabacionPagoLink = computed(() => props.actividad?.grabacion?.boton_pago?.link || '');

const comidasDisponibles = computed(() => props.actividad?.comidas || []);
const totalComidas = computed(() => {
  return comidasDisponibles.value
    .filter((comida) => comidasSeleccionadas.value.includes(comida.id))
    .reduce((acc, comida) => acc + (parseFloat(comida.precio || 0) || 0), 0);
});

const transportesDisponibles = computed(() => props.actividad?.transportes || []);
const totalTransportes = computed(() => {
  return transportesDisponibles.value
    .filter((transporte) => transportesSeleccionados.value.includes(transporte.id))
    .reduce((acc, transporte) => acc + (parseFloat(transporte.precio || 0) || 0), 0);
});

const hospedajesDisponibles = computed(() => props.actividad?.hospedajes || []);
const totalHospedajes = computed(() => {
  return hospedajesDisponibles.value
    .filter((hospedaje) => hospedajesSeleccionados.value.includes(hospedaje.id))
    .reduce((acc, hospedaje) => acc + (parseFloat(hospedaje.precio || 0) || 0), 0);
});

const saldoAPagar = computed(() => {
  const totalGrabacion = grabacionSeleccionada.value ? grabacionPrecio.value : 0;
  return actividadPrecio.value + totalGrabacion + totalComidas.value + totalTransportes.value + totalHospedajes.value;
});
const esPagoCero = computed(() => {
  return (
    saldoFinal.value <= 0 ||
    props.actividad?.gratuita === true ||
    props.actividad?.es_gratuita === true
  );
});

const puedeFinalizar = computed(() => {
  if (esPagoCero.value) return true;
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
      pago_metodo: pagoMetodo.value || (esPagoCero.value ? 'gratis' : 'efectivo'),
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
          <h2 class="text-2xl font-semibold text-gray-800">
            {{ actividad.nombre }}
          </h2>
                    <div v-if="!esPagoCero" class="mt-4">
            <p class="mt-2 text-sm text-gray-700 mb-2">Medios de pago disponibles:</p>
            <div class="flex flex-wrap gap-2">
              <Tag
                v-for="metodo in actividad.metodos_pago"
                :key="metodo.id"
                severity="info"
                :value="metodo.nombre"
              />
            </div>
          </div>
          <p class="text-lg text-gray-600 mt-4">
            Valor de la actividad: <span class="font-semibold text-gray-800">$ {{ saldoFinal.toLocaleString('es-AR') }}</span>
          </p>

          <div class="mt-2 flex flex-wrap items-center gap-2">
            <a
              v-if="actividadPagoLink"
              :href="actividadPagoLink"
              target="_blank"
              class="inline-flex items-center px-3 py-1 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700"
            >
              Pagar Actividad
            </a>
            <span v-else class="text-xs text-gray-400">Sin botón de pago disponible</span>
          </div>
          <p class="text-lg text-green-600 mt-1">
            Membresía aplicada: {{ membresia }}
          </p>


          <div class="mt-6 space-y-4">
            <div v-if="grabacionDisponible" class="border rounded-lg p-4">
              <div class="flex items-center gap-2">
                <Checkbox
                  inputId="grabacion_check"
                  v-model="grabacionSeleccionada"
                  binary
                />
                <label for="grabacion_check" class="text-sm font-semibold text-gray-700">
                  Agregar grabación
                </label>
              </div>
              <div v-if="grabacionSeleccionada" class="mt-2 text-sm text-gray-700">
                <div class="flex flex-wrap items-center gap-2">
                  <span>Valor de grabación:</span>
                  <span class="font-semibold">$ {{ grabacionPrecio.toLocaleString('es-AR') }}</span>
                  <a
                    v-if="grabacionPagoLink"
                    :href="grabacionPagoLink"
                    target="_blank"
                    class="inline-flex items-center px-3 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700"
                  >
                    Pagar grabación
                  </a>
                  <span v-else class="text-xs text-gray-400">Sin botón de pago</span>
                </div>
              </div>
            </div>

            <div v-if="comidasDisponibles.length" class="border rounded-lg p-4">
              <p class="text-sm font-semibold text-gray-700 mb-2">Comidas</p>
              <div class="space-y-2">
                <div
                  v-for="comida in comidasDisponibles"
                  :key="comida.id"
                  class="flex flex-wrap items-center justify-between gap-2"
                >
                  <label class="flex items-center gap-2 text-sm text-gray-700">
                    <Checkbox
                      :inputId="`comida_${comida.id}`"
                      :value="comida.id"
                      v-model="comidasSeleccionadas"
                    />
                    {{ comida.nombre }}
                  </label>
                  <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-700">$ {{ (parseFloat(comida.precio || 0) || 0).toLocaleString('es-AR') }}</span>
                    <a
                      v-if="comidasSeleccionadas.includes(comida.id) && comida.boton_pago?.link"
                      :href="comida.boton_pago.link"
                      target="_blank"
                      class="inline-flex items-center px-2 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700"
                    >
                      Pagar
                    </a>
                    <span v-else-if="comidasSeleccionadas.includes(comida.id)" class="text-xs text-gray-400">Sin bot�n</span>
                  </div>
                </div>
              </div>
              <div class="mt-3 text-sm text-gray-800">
                Total Comidas: <span class="font-semibold">$ {{ totalComidas.toLocaleString('es-AR') }}</span>
              </div>
            </div>

            <div v-if="transportesDisponibles.length" class="border rounded-lg p-4">
              <p class="text-sm font-semibold text-gray-700 mb-2">Transportes</p>
              <div class="space-y-2">
                <div
                  v-for="transporte in transportesDisponibles"
                  :key="transporte.id"
                  class="flex flex-wrap items-center justify-between gap-2"
                >
                  <label class="flex items-center gap-2 text-sm text-gray-700">
                    <Checkbox
                      :inputId="`transporte_${transporte.id}`"
                      :value="transporte.id"
                      v-model="transportesSeleccionados"
                    />
                    {{ transporte.descripcion || transporte.nombre }}
                  </label>
                  <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-700">$ {{ (parseFloat(transporte.precio || 0) || 0).toLocaleString('es-AR') }}</span>
                    <a
                      v-if="transportesSeleccionados.includes(transporte.id) && transporte.boton_pago?.link"
                      :href="transporte.boton_pago.link"
                      target="_blank"
                      class="inline-flex items-center px-2 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700"
                    >
                      Pagar
                    </a>
                    <span v-else-if="transportesSeleccionados.includes(transporte.id)" class="text-xs text-gray-400">Sin bot�n</span>
                  </div>
                </div>
              </div>
              <div class="mt-3 text-sm text-gray-800">
                Total Transportes: <span class="font-semibold">$ {{ totalTransportes.toLocaleString('es-AR') }}</span>
              </div>
            </div>


            <div v-if="hospedajesDisponibles.length" class="border rounded-lg p-4">
              <p class="text-sm font-semibold text-gray-700 mb-2">Lugar de hospedaje</p>
              <div class="text-sm text-gray-700 mb-3">
                <template v-if="hospedajesDisponibles.some(h => h.lugar_hospedaje)">
                  <span
                    v-for="(lugar, idx) in Array.from(new Set(hospedajesDisponibles.map(h => h.lugar_hospedaje?.nombre).filter(Boolean)))"
                    :key="lugar"
                  >
                    {{ lugar }}<span v-if="idx < Array.from(new Set(hospedajesDisponibles.map(h => h.lugar_hospedaje?.nombre).filter(Boolean))).length - 1">, </span>
                  </span>
                </template>
                <span v-else class="text-xs text-gray-400">Sin lugar definido</span>
              </div>

              <p class="text-sm font-semibold text-gray-700 mb-2">Hospedajes</p>
              <div class="space-y-2">
                <div
                  v-for="hospedaje in hospedajesDisponibles"
                  :key="hospedaje.id"
                  class="flex flex-wrap items-center justify-between gap-2"
                >
                  <label class="flex items-center gap-2 text-sm text-gray-700">
                    <Checkbox
                      :inputId="`hospedaje_${hospedaje.id}`"
                      :value="hospedaje.id"
                      v-model="hospedajesSeleccionados"
                    />
                    {{ hospedaje.nombre }}
                  </label>
                  <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-700">$ {{ (parseFloat(hospedaje.precio || 0) || 0).toLocaleString('es-AR') }}</span>
                    <a
                      v-if="hospedajesSeleccionados.includes(hospedaje.id) && hospedaje.boton_pago?.link"
                      :href="hospedaje.boton_pago.link"
                      target="_blank"
                      class="inline-flex items-center px-2 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700"
                    >
                      Pagar
                    </a>
                    <span v-else-if="hospedajesSeleccionados.includes(hospedaje.id)" class="text-xs text-gray-400">Sin bot�n</span>
                  </div>
                </div>
              </div>
              <div class="mt-3 text-sm text-gray-800">
                Total Hospedaje: <span class="font-semibold">$ {{ totalHospedajes.toLocaleString('es-AR') }}</span>
              </div>
            </div>

            <div class="border rounded-lg p-4 bg-gray-50">
              <p class="text-sm text-gray-700">
                Saldo a Pagar:
                <span class="font-semibold text-gray-800">$ {{ saldoAPagar.toLocaleString('es-AR') }}</span>
              </p>
            </div>
          </div>

          

          <div v-if="!esPagoCero" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-if="tieneEfectivo" class="border rounded-lg p-4">
              <h3 class="text-sm font-semibold text-gray-700">Pago en efectivo</h3>
              <p class="text-sm text-green-600 mt-1">
                {{ descripcionEfectivo }} Tu inscripción quedará en estado pendiente para aprobación.
              </p>
              <!-- <button
                class="mt-3 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700"
                @click="pagoMetodo = 'efectivo'"
              >
                Pagaré en efectivo más tarde
              </button> -->
            </div>
            <div v-if="tieneTransferencia" class="border rounded-lg p-4">
              <h3 class="text-sm font-semibold text-gray-700">Pagar por transferencia</h3>
              <p class="text-sm text-green-600 mt-1">
                {{ descripcionTransferencia }}
              </p>
              <div class="mt-4 flex flex-wrap gap-2">
                <h3 class="text-sm font-semibold text-gray-900">Recuerda subir el comprobante si pagaste por transferencia o Getnet</h3>
                <button
                  class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
                  @click="comprobanteModal = true"
                >
                  Subir comprobante
                </button>

              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end">
            <button
              class="px-5 py-2 rounded text-white bg-green-600 hover:bg-green-700 disabled:opacity-60"
              :disabled="isFinalizing"
              @click="terminar"
            >
              {{ isFinalizing ? 'Finalizando...' : 'Terminar inscripción' }}
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







<style scoped>
:deep(.p-checkbox .p-checkbox-box) {
  border: 1px solid #9ca3af;
  background: #ffffff;
}
:deep(.p-checkbox .p-checkbox-box .p-checkbox-icon) {
  color: #111827;
  font-size: 0.8rem;
}
:deep(.p-checkbox .p-checkbox-box.p-highlight) {
  border-color: #4f46e5;
  background: #4f46e5;
}
:deep(.p-checkbox .p-checkbox-box.p-highlight .p-checkbox-icon) {
  color: #ffffff;
}
</style>



















