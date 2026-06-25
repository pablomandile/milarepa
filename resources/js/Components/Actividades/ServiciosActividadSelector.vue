<script setup>
import { computed } from 'vue';
import Checkbox from 'primevue/checkbox';

const props = defineProps({
  actividad: { type: Object, required: true },
  grabacion: { type: Boolean, default: false },
  comidas: { type: Array, default: () => [] },
  transportes: { type: Array, default: () => [] },
  hospedajes: { type: Array, default: () => [] },
  // Funciones inyectadas desde el padre para resolver precios por moneda y formatear.
  resolverPrecio: { type: Function, required: true },
  formatMoney: { type: Function, required: true },
  simboloMoneda: { type: String, default: '$' },
  // Bloqueos (solo aplican al principal en pagos de inscripción existente).
  grabacionBloqueada: { type: Boolean, default: false },
  comidasBloqueadasIds: { type: Array, default: () => [] },
  transportesBloqueadosIds: { type: Array, default: () => [] },
  hospedajesBloqueadosIds: { type: Array, default: () => [] },
  mostrarBotonesPago: { type: Boolean, default: false },
  // Prefijo para ids de inputs (evita colisiones con múltiples selectores en la página).
  idPrefix: { type: String, default: 'serv' },
});

const emit = defineEmits([
  'update:grabacion',
  'update:comidas',
  'update:transportes',
  'update:hospedajes',
]);

const grabacionModel = computed({
  get: () => props.grabacion,
  set: (v) => emit('update:grabacion', v),
});
const comidasModel = computed({
  get: () => props.comidas,
  set: (v) => emit('update:comidas', v),
});
const transportesModel = computed({
  get: () => props.transportes,
  set: (v) => emit('update:transportes', v),
});
const hospedajesModel = computed({
  get: () => props.hospedajes,
  set: (v) => emit('update:hospedajes', v),
});

const grabacionDisponible = computed(() => !!props.actividad?.grabacion_id && !!props.actividad?.grabacion);
const grabacionPrecio = computed(() => props.resolverPrecio(props.actividad?.grabacion || {}, 'valor').precio);
const grabacionSimbolo = computed(() => props.resolverPrecio(props.actividad?.grabacion || {}, 'valor').simbolo);
const grabacionPagoLink = computed(() => props.actividad?.grabacion?.boton_pago?.link || '');

const comidasDisponibles = computed(() => props.actividad?.comidas || []);
const precioComida = (comida) => props.resolverPrecio(comida, 'precio');
const totalComidas = computed(() => comidasDisponibles.value
  .filter((c) => props.comidas.includes(c.id))
  .reduce((acc, c) => acc + precioComida(c).precio, 0));

const transportesDisponibles = computed(() => props.actividad?.transportes || []);
const precioTransporte = (transporte) => props.resolverPrecio(transporte, 'precio');
const totalTransportes = computed(() => transportesDisponibles.value
  .filter((t) => props.transportes.includes(t.id))
  .reduce((acc, t) => acc + precioTransporte(t).precio, 0));

const hospedajesDisponibles = computed(() => props.actividad?.hospedajes || []);
const precioHospedaje = (hospedaje) => props.resolverPrecio(hospedaje, 'precio');
const totalHospedajes = computed(() => hospedajesDisponibles.value
  .filter((h) => props.hospedajes.includes(h.id))
  .reduce((acc, h) => acc + precioHospedaje(h).precio, 0));

const lugaresHospedaje = computed(() => Array.from(
  new Set(hospedajesDisponibles.value.map((h) => h.lugar_hospedaje?.nombre).filter(Boolean))
));
</script>

<template>
  <div class="space-y-4">
    <div v-if="grabacionDisponible" class="border rounded-lg p-4">
      <div class="flex items-center gap-2">
        <Checkbox
          :inputId="`${idPrefix}_grabacion`"
          v-model="grabacionModel"
          binary
          :disabled="grabacionBloqueada"
        />
        <label :for="`${idPrefix}_grabacion`" class="text-sm font-semibold text-gray-700">
          Agregar grabación
        </label>
      </div>
      <div v-if="grabacion" class="mt-2 text-sm text-gray-700">
        <div class="flex flex-wrap items-center gap-2">
          <span>Valor de grabación:</span>
          <span class="font-semibold">{{ formatMoney(grabacionPrecio, grabacionSimbolo) }}</span>
          <a
            v-if="mostrarBotonesPago && grabacionPagoLink"
            :href="grabacionPagoLink"
            target="_blank"
            class="inline-flex items-center px-3 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700"
          >
            Pagar grabación
          </a>
          <span v-else-if="mostrarBotonesPago" class="text-xs text-gray-400">Sin botón de pago</span>
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
              :inputId="`${idPrefix}_comida_${comida.id}`"
              :value="comida.id"
              v-model="comidasModel"
              :disabled="comidasBloqueadasIds.includes(comida.id)"
            />
            {{ comida.nombre }}
          </label>
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-700">{{ formatMoney(precioComida(comida).precio, precioComida(comida).simbolo) }}</span>
            <a
              v-if="mostrarBotonesPago && comidas.includes(comida.id) && comida.boton_pago?.link"
              :href="comida.boton_pago.link"
              target="_blank"
              class="inline-flex items-center px-2 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700"
            >
              Pagar
            </a>
            <span v-else-if="mostrarBotonesPago && comidas.includes(comida.id)" class="text-xs text-gray-400">Sin botón</span>
          </div>
        </div>
      </div>
      <div class="mt-3 text-sm text-gray-800">
        Total Comidas: <span class="font-semibold">{{ formatMoney(totalComidas) }}</span>
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
              :inputId="`${idPrefix}_transporte_${transporte.id}`"
              :value="transporte.id"
              v-model="transportesModel"
              :disabled="transportesBloqueadosIds.includes(transporte.id)"
            />
            {{ transporte.descripcion || transporte.nombre }}
          </label>
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-700">{{ formatMoney(precioTransporte(transporte).precio, precioTransporte(transporte).simbolo) }}</span>
            <a
              v-if="mostrarBotonesPago && transportes.includes(transporte.id) && transporte.boton_pago?.link"
              :href="transporte.boton_pago.link"
              target="_blank"
              class="inline-flex items-center px-2 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700"
            >
              Pagar
            </a>
            <span v-else-if="mostrarBotonesPago && transportes.includes(transporte.id)" class="text-xs text-gray-400">Sin botón</span>
          </div>
        </div>
      </div>
      <div class="mt-3 text-sm text-gray-800">
        Total Transportes: <span class="font-semibold">{{ formatMoney(totalTransportes) }}</span>
      </div>
    </div>

    <div v-if="hospedajesDisponibles.length" class="border rounded-lg p-4">
      <p class="text-sm font-semibold text-gray-700 mb-2">Lugar de hospedaje</p>
      <div class="text-sm text-gray-700 mb-3">
        <template v-if="lugaresHospedaje.length">
          <span
            v-for="(lugar, idx) in lugaresHospedaje"
            :key="lugar"
          >
            {{ lugar }}<span v-if="idx < lugaresHospedaje.length - 1">, </span>
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
              :inputId="`${idPrefix}_hospedaje_${hospedaje.id}`"
              :value="hospedaje.id"
              v-model="hospedajesModel"
              :disabled="hospedajesBloqueadosIds.includes(hospedaje.id)"
            />
            {{ hospedaje.nombre }}
          </label>
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-700">{{ formatMoney(precioHospedaje(hospedaje).precio, precioHospedaje(hospedaje).simbolo) }}</span>
            <a
              v-if="mostrarBotonesPago && hospedajes.includes(hospedaje.id) && hospedaje.boton_pago?.link"
              :href="hospedaje.boton_pago.link"
              target="_blank"
              class="inline-flex items-center px-2 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700"
            >
              Pagar
            </a>
            <span v-else-if="mostrarBotonesPago && hospedajes.includes(hospedaje.id)" class="text-xs text-gray-400">Sin botón</span>
          </div>
        </div>
      </div>
      <div class="mt-3 text-sm text-gray-800">
        Total Hospedaje: <span class="font-semibold">{{ formatMoney(totalHospedajes) }}</span>
      </div>
    </div>
  </div>
</template>
