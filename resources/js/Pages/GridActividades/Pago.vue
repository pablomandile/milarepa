<script setup>
import { computed, ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Checkbox from 'primevue/checkbox';
import { useToast } from 'primevue/usetoast';
import ServiciosActividadSelector from '@/Components/Actividades/ServiciosActividadSelector.vue';

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
  mostrarSelectorModalidad: {
    type: Boolean,
    default: false,
  },
  inscripcion: {
    type: Object,
    default: null,
  },
});

const toast = useToast();
const page = usePage();
const comprobanteModal = ref(false);
const comprobanteFile = ref(null);
const comprobanteDescripcion = ref('');
const isUploading = ref(false);
const isFinalizing = ref(false);
const modalidadCursada = ref('presencial');
const metodosPago = computed(() =>
  (props.actividad?.metodos_pago || []).map((metodo) => ({
    id: metodo.id,
    nombre: metodo.nombre,
    tipo: metodo.tipo_de_pago || '',
    descripcion: metodo.descripcion || '',
    label: metodo.tipo_de_pago ? `${metodo.nombre} (${metodo.tipo_de_pago})` : metodo.nombre,
    value: normalizarMetodoPago(metodo.nombre || ''),
  }))
);
const pagoMetodo = ref(null);
const comprobantePath = ref(props.pago?.comprobante_path || null);
const monedaSeleccionadaId = ref(null);

const saldoFinal = computed(() => parseFloat(props.saldo || 0));
const actividadEsGratuita = computed(() => {
  const nombreEsquema = (props.actividad?.esquema_precio?.nombre || '').toString().toLowerCase();
  return (
    props.actividad?.gratuita === true ||
    props.actividad?.es_gratuita === true ||
    nombreEsquema.includes('gratuita')
  );
});
const actividadPagoLink = computed(() => props.actividad?.boton_pago?.link || '');
const grabacionSeleccionada = ref(false);
const comidasSeleccionadas = ref([]);
const transportesSeleccionados = ref([]);
const hospedajesSeleccionados = ref([]);

const normalizarNombre = (value) => String(value || '')
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toLowerCase()
  .trim();

const membresiaIdUsuario = computed(() => {
  return (
    page.props?.auth?.user?.membresia?.id ||
    page.props?.auth?.user?.membresia_id ||
    null
  );
});

const fechaLimiteDescuento = computed(() => {
  if (!props.actividad?.pagoAmticipado) return null;
  const fecha = new Date(props.actividad.pagoAmticipado);
  return Number.isNaN(fecha.getTime()) ? null : fecha;
});

const descuentoVigente = computed(() => {
  if (!props.actividad?.esquema_descuento || !fechaLimiteDescuento.value) return false;
  return new Date() <= fechaLimiteDescuento.value;
});

const esquemaVigente = computed(() => {
  if (descuentoVigente.value && props.actividad?.esquema_descuento) {
    return props.actividad.esquema_descuento;
  }
  return props.actividad?.esquema_precio || null;
});

const esMembresiaGeneral = (linea) => {
  const nombre = normalizarNombre(linea?.membresia?.nombre);
  return nombre === 'sin membresia' || nombre.includes('sin membres');
};

const lineasEsquemaConMoneda = computed(() => {
  const lineas = esquemaVigente.value?.membresias || [];
  return lineas.filter((linea) => {
    const precio = Number(linea?.precio);
    return Number.isFinite(precio) && linea?.moneda_id;
  });
});

const monedasDisponibles = computed(() => {
  const mapa = new Map();
  for (const linea of lineasEsquemaConMoneda.value) {
    const moneda = linea?.moneda;
    if (!moneda?.id) continue;
    if (!mapa.has(moneda.id)) {
      mapa.set(moneda.id, {
        id: moneda.id,
        nombre: moneda.nombre || `Moneda ${moneda.id}`,
        simbolo: moneda.simbolo || '$',
      });
    }
  }
  return Array.from(mapa.values());
});

const mostrarSelectorMoneda = computed(() => monedasDisponibles.value.length > 1);

const monedaSeleccionada = computed(() => {
  if (!monedasDisponibles.value.length) return null;
  return monedasDisponibles.value.find((m) => m.id === monedaSeleccionadaId.value) || monedasDisponibles.value[0];
});

const simboloMoneda = computed(() => monedaSeleccionada.value?.simbolo || '$');

const formatoNumero = (valor) => {
  const numero = Number(valor || 0);
  if (!Number.isFinite(numero)) return '0,00';
  return new Intl.NumberFormat('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(numero);
};

const formatMoney = (valor, simbolo = simboloMoneda.value) => {
  return `${simbolo} ${formatoNumero(valor)}`;
};

const obtenerLineaPrecio = (lineas, membresiaId, monedaId) => {
  if (!Array.isArray(lineas) || !lineas.length) return null;
  if (membresiaId && monedaId) {
    const exacta = lineas.find((linea) => linea?.membresia_id === membresiaId && linea?.moneda_id === monedaId);
    if (exacta) return exacta;
  }
  if (monedaId) {
    const generalMoneda = lineas.find((linea) => esMembresiaGeneral(linea) && linea?.moneda_id === monedaId);
    if (generalMoneda) return generalMoneda;
  }
  if (membresiaId) {
    const membresiaCualquiera = lineas.find((linea) => linea?.membresia_id === membresiaId);
    if (membresiaCualquiera) return membresiaCualquiera;
  }
  return lineas.find((linea) => esMembresiaGeneral(linea)) || lineas[0] || null;
};

const lineaActividadActual = computed(() => {
  return obtenerLineaPrecio(
    esquemaVigente.value?.membresias || [],
    membresiaIdUsuario.value,
    monedaSeleccionadaId.value
  );
});

const resolverPrecioItemEnMoneda = (item, campoBase = 'precio') => {
  const monedaId = monedaSeleccionadaId.value;
  const preciosPorMoneda = item?.precios_por_moneda || item?.preciosPorMoneda || item?.precios || [];

  if (Array.isArray(preciosPorMoneda) && preciosPorMoneda.length) {
    const match = preciosPorMoneda.find((linea) => {
      const lineaMonedaId = linea?.moneda_id || linea?.moneda?.id;
      return monedaId && lineaMonedaId === monedaId;
    }) || preciosPorMoneda[0];
    const precioLinea = Number(match?.precio ?? match?.valor ?? 0);
    const simbolo = match?.moneda?.simbolo || simboloMoneda.value;
    return {
      precio: Number.isFinite(precioLinea) ? precioLinea : 0,
      simbolo,
    };
  }

  const mapaPrecios = item?.precios_moneda || item?.preciosMoneda;
  if (mapaPrecios && typeof mapaPrecios === 'object' && monedaId && mapaPrecios[monedaId] !== undefined) {
    const valor = Number(mapaPrecios[monedaId]);
    return {
      precio: Number.isFinite(valor) ? valor : 0,
      simbolo: simboloMoneda.value,
    };
  }

  const valorBase = Number(item?.[campoBase] ?? 0);
  return {
    precio: Number.isFinite(valorBase) ? valorBase : 0,
    simbolo: simboloMoneda.value,
  };
};

const actividadPrecio = computed(() => {
  if (lineaActividadActual.value?.precio !== undefined && lineaActividadActual.value?.precio !== null) {
    return Number(lineaActividadActual.value.precio) || 0;
  }
  return parseFloat(props.saldo || 0) || 0;
});
const actividadSimbolo = computed(() => lineaActividadActual.value?.moneda?.simbolo || simboloMoneda.value);
const grabacionDisponible = computed(() => !!props.actividad?.grabacion_id && !!props.actividad?.grabacion);
const grabacionPrecio = computed(() => {
  return resolverPrecioItemEnMoneda(props.actividad?.grabacion || {}, 'valor').precio;
});
const grabacionSimbolo = computed(() => resolverPrecioItemEnMoneda(props.actividad?.grabacion || {}, 'valor').simbolo);
const grabacionPagoLink = computed(() => props.actividad?.grabacion?.boton_pago?.link || '');

const comidasDisponibles = computed(() => props.actividad?.comidas || []);
const precioComida = (comida) => resolverPrecioItemEnMoneda(comida, 'precio');
const totalComidas = computed(() => {
  return comidasDisponibles.value
    .filter((comida) => comidasSeleccionadas.value.includes(comida.id))
    .reduce((acc, comida) => acc + precioComida(comida).precio, 0);
});

const transportesDisponibles = computed(() => props.actividad?.transportes || []);
const precioTransporte = (transporte) => resolverPrecioItemEnMoneda(transporte, 'precio');
const totalTransportes = computed(() => {
  return transportesDisponibles.value
    .filter((transporte) => transportesSeleccionados.value.includes(transporte.id))
    .reduce((acc, transporte) => acc + precioTransporte(transporte).precio, 0);
});

const hospedajesDisponibles = computed(() => props.actividad?.hospedajes || []);
const precioHospedaje = (hospedaje) => resolverPrecioItemEnMoneda(hospedaje, 'precio');
const totalHospedajes = computed(() => {
  return hospedajesDisponibles.value
    .filter((hospedaje) => hospedajesSeleccionados.value.includes(hospedaje.id))
    .reduce((acc, hospedaje) => acc + precioHospedaje(hospedaje).precio, 0);
});

// --- Invitados ---------------------------------------------------------------
// Los invitados pagan SIEMPRE el precio general de la actividad (sin descuento).
const lineaGeneralActividad = computed(() => obtenerLineaPrecio(
  esquemaVigente.value?.membresias || [],
  null,
  monedaSeleccionadaId.value
));
const precioGeneralActividad = computed(() => {
  if (lineaGeneralActividad.value?.precio !== undefined && lineaGeneralActividad.value?.precio !== null) {
    return Number(lineaGeneralActividad.value.precio) || 0;
  }
  return actividadPrecio.value;
});

const modalidadActividadAbierta = computed(
  () => normalizarNombre(props.actividad?.modalidad?.nombre) === 'presencial y online abierta'
);
// El flujo de invitados aplica a eventos con componente presencial.
const permiteInvitados = computed(
  () => normalizarNombre(props.actividad?.modalidad?.nombre) !== 'online'
);

const MAX_INVITADOS = 10;
const invitados = ref([]);
const invitadoDialog = ref(false);

const nuevoInvitado = () => ({
  nombre: '',
  apellido: '',
  telefono: '',
  online: false,
  grabacion: false,
  comidas: [],
  transportes: [],
  hospedajes: [],
});
const invitadoForm = ref(nuevoInvitado());

const subtotalInvitado = (invitado) => {
  const totalGrabacion = invitado.grabacion ? grabacionPrecio.value : 0;
  const totalComidasInv = comidasDisponibles.value
    .filter((c) => invitado.comidas.includes(c.id))
    .reduce((acc, c) => acc + precioComida(c).precio, 0);
  const totalTransportesInv = transportesDisponibles.value
    .filter((t) => invitado.transportes.includes(t.id))
    .reduce((acc, t) => acc + precioTransporte(t).precio, 0);
  const totalHospedajesInv = hospedajesDisponibles.value
    .filter((h) => invitado.hospedajes.includes(h.id))
    .reduce((acc, h) => acc + precioHospedaje(h).precio, 0);
  return precioGeneralActividad.value + totalGrabacion + totalComidasInv + totalTransportesInv + totalHospedajesInv;
};

const totalInvitados = computed(() => invitados.value.reduce((acc, inv) => acc + subtotalInvitado(inv), 0));

const abrirDialogInvitado = () => {
  invitadoForm.value = nuevoInvitado();
  invitadoDialog.value = true;
};
const guardarInvitado = () => {
  const f = invitadoForm.value;
  if (!f.nombre.trim() || !f.apellido.trim()) {
    toast.add({ severity: 'warn', summary: 'Invitado', detail: 'Nombre y apellido son obligatorios.', life: 4000 });
    return;
  }
  if (invitados.value.length >= MAX_INVITADOS) {
    toast.add({ severity: 'warn', summary: 'Invitados', detail: `Máximo ${MAX_INVITADOS} invitados.`, life: 4000 });
    return;
  }
  invitados.value.push({
    ...f,
    nombre: f.nombre.trim(),
    apellido: f.apellido.trim(),
    telefono: (f.telefono || '').trim(),
    online: modalidadActividadAbierta.value ? !!f.online : false,
  });
  invitadoDialog.value = false;
};
const eliminarInvitado = (index) => {
  invitados.value.splice(index, 1);
};

const saldoAPagar = computed(() => {
  const totalGrabacion = grabacionSeleccionada.value ? grabacionPrecio.value : 0;
  return actividadPrecio.value + totalGrabacion + totalComidas.value + totalTransportes.value + totalHospedajes.value + totalInvitados.value;
});
const esPagoDeInscripcionExistente = computed(() => !!props.pago?.inscripcion_id);
const comidasBloqueadasIds = computed(() => {
  if (!esPagoDeInscripcionExistente.value || !props.inscripcion) return [];
  if (Array.isArray(props.inscripcion.comidas) && props.inscripcion.comidas.length) {
    return props.inscripcion.comidas.map((comida) => comida.id);
  }
  return props.inscripcion.comida_id ? [props.inscripcion.comida_id] : [];
});
const transportesBloqueadosIds = computed(() => {
  if (!esPagoDeInscripcionExistente.value || !props.inscripcion?.transporte_id) return [];
  return [props.inscripcion.transporte_id];
});
const hospedajesBloqueadosIds = computed(() => {
  if (!esPagoDeInscripcionExistente.value || !props.inscripcion?.hospedaje_id) return [];
  return [props.inscripcion.hospedaje_id];
});
const grabacionBloqueada = computed(() => {
  return esPagoDeInscripcionExistente.value && !!props.inscripcion?.montoGrabacion && Number(props.inscripcion.montoGrabacion) > 0;
});
const esPagoCero = computed(() => {
  // Si hay un monto real a pagar (incluye invitados y servicios) se habilitan los
  // medios de pago, aunque la actividad base sea gratuita o esté incluida.
  return saldoAPagar.value <= 0;
});

const normalizarMetodoPago = (valor) => {
  return (valor || '')
    .toString()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim();
};

const metodoSeleccionado = computed(() => normalizarMetodoPago(pagoMetodo.value || ''));
const metodoPagoSeleccionado = computed(() => {
  return metodosPago.value.find((metodo) => metodo.value === metodoSeleccionado.value) || null;
});
const tipoMetodoSeleccionado = computed(() => normalizarMetodoPago(metodoPagoSeleccionado.value?.tipo || ''));
const esMetodoPresencialPorTipo = computed(() => tipoMetodoSeleccionado.value.includes('presencial'));

const esMetodoTipoEfectivo = computed(() => {
  if (esMetodoPresencialPorTipo.value) return true;
  return ['efectivo', 'tarjeta de credito', 'tarjeta de debito'].includes(metodoSeleccionado.value);
});

const puedeFinalizar = computed(() => {
  if (esPagoCero.value) return true;
  if (!pagoMetodo.value) return false;
  if (esMetodoTipoEfectivo.value) return true;
  return ['transferencia', 'getnet'].includes(pagoMetodo.value) || !!comprobantePath.value;
});
const esEfectivoSeleccionado = computed(() => metodoSeleccionado.value === 'efectivo');
const esTransferenciaSeleccionado = computed(() => metodoSeleccionado.value === 'transferencia');
const esGetnetSeleccionado = computed(() => metodoSeleccionado.value === 'getnet');
const mostrarBotonesPago = computed(() => {
  return !esPagoCero.value && esGetnetSeleccionado.value;
});
const mostrarInfoEfectivo = computed(() => !esPagoCero.value && esMetodoTipoEfectivo.value);
const mostrarInfoTransferencia = computed(() => !esPagoCero.value && esTransferenciaSeleccionado.value);
const mostrarInfoGetnet = computed(() => !esPagoCero.value && esGetnetSeleccionado.value);

const descripcionEfectivo = computed(() => {
  const metodo = props.actividad.metodos_pago?.find(
    (m) => m.nombre?.toLowerCase() === 'efectivo'
  );
  return metodo?.descripcion || 'Registrá el pago en efectivo. La inscripción quedará en estado pendiente para aprobación.';
});

const tituloMetodoTipoEfectivo = computed(() => {
  if (esMetodoPresencialPorTipo.value) {
    return `Pago presencial${metodoPagoSeleccionado.value?.nombre ? ` (${metodoPagoSeleccionado.value.nombre})` : ''}`;
  }
  return 'Pago en efectivo';
});
const direccionPagoMetodoTipoEfectivo = computed(() => {
  return props.actividad?.lugar?.direccion || props.actividad?.entidad?.direccion || '';
});
const descripcionMetodoTipoEfectivo = computed(() => {
  if (esMetodoTipoEfectivo.value) {
    return 'Podés pagar con tarjeta de débito en el lugar antes de comenzar. Tu inscripción quedará en estado pendiente para aprobación.';
  }
  return `${descripcionEfectivo.value} Tu inscripción quedará en estado pendiente para aprobación.`;
});

const descripcionTransferencia = computed(() => {
  const metodo = props.actividad.metodos_pago?.find(
    (m) => m.nombre?.toLowerCase() === 'transferencia'
  );
  return metodo?.descripcion || 'Subí un comprobante (PDF o imagen) para registrar el pago.';
});

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
    if (comprobanteDescripcion.value) {
      data.append('descripcion', comprobanteDescripcion.value);
    }
    const response = await axios.post(route('grid-actividades.pago.comprobante'), data);
    comprobantePath.value = response.data.path;
    pagoMetodo.value = 'comprobante';
    comprobanteModal.value = false;
    comprobanteDescripcion.value = '';
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
      moneda_id: monedaSeleccionadaId.value,
      incluye_grabacion: grabacionSeleccionada.value,
      modalidad_cursada: props.mostrarSelectorModalidad ? modalidadCursada.value : null,
      comidas_ids: comidasSeleccionadas.value,
      transportes_ids: transportesSeleccionados.value,
      hospedajes_ids: hospedajesSeleccionados.value,
      invitados: invitados.value.map((inv) => ({
        nombre: inv.nombre,
        apellido: inv.apellido,
        telefono: inv.telefono || null,
        online: modalidadActividadAbierta.value ? !!inv.online : false,
        incluye_grabacion: !!inv.grabacion,
        comidas_ids: inv.comidas,
        transportes_ids: inv.transportes,
        hospedajes_ids: inv.hospedajes,
      })),
    });
    const inscripcionId = response.data?.inscripcion_id;
    const registrado = response.data?.registered;
    const canViewPrivate = !!response.data?.can_view_private;
    const updatedExisting = !!response.data?.updated_existing;
    if (inscripcionId) {
      if (updatedExisting) {
        window.location.href = route('inscripciones.index');
        return;
      }
      if (registrado && canViewPrivate) {
        window.location.href = route('inscripciones.show', { inscripcion: inscripcionId });
      } else if (response.data?.public_url) {
        // URL firmada generada por el backend (válida 180 días). No reconstruir
        // la URL con route() porque la ruta exige firma y devolvería 403.
        window.location.href = response.data.public_url;
      } else {
        window.location.href = route('grid-actividades.index');
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

watch(
  monedasDisponibles,
  (monedas) => {
    if (!Array.isArray(monedas) || !monedas.length) {
      monedaSeleccionadaId.value = null;
      return;
    }
    if (!monedaSeleccionadaId.value || !monedas.some((m) => m.id === monedaSeleccionadaId.value)) {
      monedaSeleccionadaId.value = monedas[0].id;
    }
  },
  { immediate: true }
);

watch(
  () => props.inscripcion,
  (inscripcion) => {
    if (!inscripcion) return;

    if (inscripcion.comida_id) {
      comidasSeleccionadas.value = [inscripcion.comida_id];
    }
    if (Array.isArray(inscripcion.comidas) && inscripcion.comidas.length) {
      comidasSeleccionadas.value = inscripcion.comidas.map((comida) => comida.id);
    }

    transportesSeleccionados.value = inscripcion.transporte_id ? [inscripcion.transporte_id] : [];
    hospedajesSeleccionados.value = inscripcion.hospedaje_id ? [inscripcion.hospedaje_id] : [];
    grabacionSeleccionada.value = !!inscripcion.montoGrabacion && Number(inscripcion.montoGrabacion) > 0;
  },
  { immediate: true }
);
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
          <h2
            v-if="actividadEsGratuita"
            class="mt-4 text-2xl font-semibold text-green-600"
          >
            ¡Esta actividad es gratuita!
          </h2>
          <h2
            v-else-if="saldoFinal <= 0"
            class="mt-4 text-2xl font-semibold text-green-600"
          >
            Esta actividad está incluída con tu membresía
          </h2>
          <p v-else class="text-lg text-gray-600 mt-4">
            Valor de la actividad: <span class="font-semibold text-gray-800">{{ formatMoney(actividadPrecio, actividadSimbolo) }}</span>
          </p>
          <p v-if="!actividadEsGratuita" class="text-lg text-green-600 mt-1">
            Membresía aplicada: {{ membresia }}
          </p>

          <div v-if="mostrarSelectorMoneda" class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-2" for="moneda_select">
              ¿En qué moneda quieres abonar?
            </label>
            <select
              id="moneda_select"
              v-model="monedaSeleccionadaId"
              :disabled="esPagoDeInscripcionExistente"
              class="w-full appearance-none rounded border border-sky-400 bg-blue-400 px-3 py-2 pr-10 text-sm text-white shadow-sm focus:border-sky-600 focus:ring focus:ring-sky-200"
            >
              <option v-for="moneda in monedasDisponibles" :key="moneda.id" :value="moneda.id">
                {{ moneda.nombre }} ({{ moneda.simbolo }})
              </option>
            </select>
          </div>

          <div v-if="mostrarSelectorModalidad" class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-2" for="modalidad_cursada_select">
              Modalidad de cursada
            </label>
            <select
              id="modalidad_cursada_select"
              v-model="modalidadCursada"
              :disabled="esPagoDeInscripcionExistente"
              class="w-full appearance-none rounded border border-sky-400 bg-blue-400 px-3 py-2 pr-10 text-sm text-white shadow-sm focus:border-sky-600 focus:ring focus:ring-sky-200"
            >
              <option value="presencial">Presencial</option>
              <option value="online">Online</option>
            </select>
          </div>

          <div v-if="!esPagoCero && metodosPago.length" class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-2" for="pago_metodo_select">
              Selecciona el Medio de pago
            </label>
            <select
              id="pago_metodo_select"
              v-model="pagoMetodo"
              class="w-full appearance-none rounded border border-sky-400 bg-blue-400 px-3 py-2 pr-10 text-sm text-white shadow-sm focus:border-sky-600 focus:ring focus:ring-sky-200"
            >
              <option :value="null" disabled>
                Selecciona un método de pago
              </option>
              <option v-for="metodo in metodosPago" :key="metodo.id" :value="metodo.value">
                {{ metodo.label }}
              </option>
            </select>
          </div>

          <div v-if="!esPagoCero" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-if="mostrarInfoEfectivo" class="border rounded-lg p-4">
              <h3 class="text-sm font-semibold text-gray-700">{{ tituloMetodoTipoEfectivo }}</h3>
              <p class="text-sm text-green-600 mt-1">
                <template v-if="esMetodoTipoEfectivo">
                  Podés pagar con tarjeta de débito en el lugar
                  <strong v-if="direccionPagoMetodoTipoEfectivo"> ({{ direccionPagoMetodoTipoEfectivo }}) </strong>
                  antes de comenzar. Tu inscripción quedará en estado pendiente para aprobación.
                </template>
                <template v-else>
                  {{ descripcionMetodoTipoEfectivo }}
                </template>
              </p>
            </div>
            <div v-if="mostrarInfoTransferencia && tieneTransferencia" class="border rounded-lg p-4">
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
            <div v-if="mostrarInfoGetnet" class="border rounded-lg p-4">
              <h3 class="text-sm font-semibold text-gray-700">Pago con Getnet</h3>
              <p class="text-sm text-green-600 mt-1">
                Subí el comprobante del pago realizado por Getnet. Puede ser más de uno.
              </p>
              <div class="mt-4 flex flex-wrap gap-2">
                <button
                  class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
                  @click="comprobanteModal = true"
                >
                  Subir comprobante
                </button>
              </div>
            </div>
          </div>

          <div class="mt-6 space-y-4">
            <div v-if="!esPagoCero" class="border rounded-lg p-4">
              <p class="text-sm font-semibold text-gray-700">Actividad</p>
              <div class="mt-2 text-sm text-gray-700">
                <div class="flex flex-wrap items-center gap-2">
                  <span>Monto a pagar de la actividad:</span>
                  <span class="font-semibold">{{ formatMoney(actividadPrecio, actividadSimbolo) }}</span>
                  <a
                    v-if="esGetnetSeleccionado && actividadPagoLink"
                    :href="actividadPagoLink"
                    target="_blank"
                    class="inline-flex items-center px-3 py-1 rounded bg-indigo-600 text-white text-xs hover:bg-indigo-700"
                  >
                    Pagar actividad
                  </a>
                  <span v-else-if="esGetnetSeleccionado" class="text-xs text-gray-400">Sin boton de pago</span>
                </div>
              </div>
            </div>

            <ServiciosActividadSelector
              :actividad="actividad"
              v-model:grabacion="grabacionSeleccionada"
              v-model:comidas="comidasSeleccionadas"
              v-model:transportes="transportesSeleccionados"
              v-model:hospedajes="hospedajesSeleccionados"
              :resolver-precio="resolverPrecioItemEnMoneda"
              :format-money="formatMoney"
              :simbolo-moneda="simboloMoneda"
              :grabacion-bloqueada="grabacionBloqueada"
              :comidas-bloqueadas-ids="comidasBloqueadasIds"
              :transportes-bloqueados-ids="transportesBloqueadosIds"
              :hospedajes-bloqueados-ids="hospedajesBloqueadosIds"
              :mostrar-botones-pago="mostrarBotonesPago"
              id-prefix="principal"
            />

            <div v-if="permiteInvitados" class="border rounded-lg p-4">
              <div class="flex items-center justify-between gap-2 mb-2">
                <p class="text-sm font-semibold text-gray-700">Invitados ({{ invitados.length }}/{{ MAX_INVITADOS }})</p>
                <button
                  type="button"
                  class="px-3 py-1 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700 disabled:bg-gray-300"
                  :disabled="invitados.length >= MAX_INVITADOS"
                  @click="abrirDialogInvitado"
                >
                  Agregar invitado
                </button>
              </div>
              <p class="text-xs text-gray-500 mb-3">
                Los invitados pagan precio general (sin descuento) y reciben los servicios que elijas para cada uno.
              </p>
              <div v-if="invitados.length" class="space-y-2">
                <div
                  v-for="(inv, idx) in invitados"
                  :key="idx"
                  class="flex flex-wrap items-center justify-between gap-2 border rounded p-2"
                >
                  <div class="text-sm text-gray-700">
                    <span class="font-semibold">{{ inv.nombre }} {{ inv.apellido }}</span>
                    <span v-if="inv.telefono" class="text-gray-500"> · {{ inv.telefono }}</span>
                    <span v-if="inv.online" class="ml-2 text-xs text-indigo-600">Online</span>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="text-sm font-semibold text-gray-800">{{ formatMoney(subtotalInvitado(inv)) }}</span>
                    <button
                      type="button"
                      class="text-red-600 text-sm hover:underline"
                      @click="eliminarInvitado(idx)"
                    >
                      Eliminar
                    </button>
                  </div>
                </div>
              </div>
              <p v-else class="text-xs text-gray-400">Sin invitados agregados.</p>
            </div>

            <div class="border rounded-lg p-4 bg-gray-50">
              <p class="text-sm text-gray-700">
                Saldo a Pagar:
                <span class="font-semibold text-gray-800">{{ formatMoney(saldoAPagar) }}</span>
              </p>
            </div>
          </div>

          

          <div class="mt-6 flex justify-end">
            <button
              class="px-5 py-2 rounded text-white disabled:cursor-not-allowed"
              :class="(isFinalizing || !puedeFinalizar) ? 'bg-gray-400' : 'bg-green-600 hover:bg-green-700'"
              :disabled="isFinalizing || !puedeFinalizar"
              @click="terminar"
            >
              {{ isFinalizing ? 'Finalizando...' : (esPagoDeInscripcionExistente ? 'Terminar Pago' : 'Terminar inscripción') }}
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
      <div class="mb-3">
        <label class="block text-sm font-medium text-gray-700 mb-1" for="comprobante_descripcion">
          Descripción (opcional)
        </label>
        <input
          id="comprobante_descripcion"
          v-model="comprobanteDescripcion"
          type="text"
          class="w-full rounded border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-indigo-500 focus:ring-indigo-500"
          placeholder="Ej: Transferencia febrero"
        />
      </div>
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

    <Dialog
      v-model:visible="invitadoDialog"
      modal
      header="Agregar invitado"
      :style="{ width: '600px' }"
      :breakpoints="{ '640px': '95vw' }"
    >
      <div class="space-y-4 pt-2">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1" for="inv_nombre">Nombre *</label>
            <input
              id="inv_nombre"
              v-model="invitadoForm.nombre"
              type="text"
              class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1" for="inv_apellido">Apellido *</label>
            <input
              id="inv_apellido"
              v-model="invitadoForm.apellido"
              type="text"
              class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="inv_telefono">Teléfono</label>
          <input
            id="inv_telefono"
            v-model="invitadoForm.telefono"
            type="text"
            class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Para seguridad/emergencia del evento"
          />
        </div>

        <div v-if="modalidadActividadAbierta" class="flex items-center gap-2">
          <Checkbox inputId="inv_online" v-model="invitadoForm.online" binary />
          <label for="inv_online" class="text-sm text-gray-700">Cursa online</label>
        </div>

        <div>
          <p class="text-sm font-semibold text-gray-700 mb-2">Servicios del invitado</p>
          <ServiciosActividadSelector
            :actividad="actividad"
            v-model:grabacion="invitadoForm.grabacion"
            v-model:comidas="invitadoForm.comidas"
            v-model:transportes="invitadoForm.transportes"
            v-model:hospedajes="invitadoForm.hospedajes"
            :resolver-precio="resolverPrecioItemEnMoneda"
            :format-money="formatMoney"
            :simbolo-moneda="simboloMoneda"
            id-prefix="invitado_form"
          />
        </div>

        <div class="border rounded-lg p-3 bg-gray-50 text-sm text-gray-800">
          Subtotal invitado: <span class="font-semibold">{{ formatMoney(subtotalInvitado(invitadoForm)) }}</span>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button class="px-4 py-2 bg-gray-500 text-white rounded" @click="invitadoDialog = false">
            Cancelar
          </button>
          <button class="px-4 py-2 bg-indigo-600 text-white rounded" @click="guardarInvitado">
            Agregar
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

#pago_metodo_select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 0.85rem;
}

#pago_metodo_select option {
  background-color: #83c6e6;
  color: #ffffff;
}

#pago_metodo_select option:hover,
#pago_metodo_select option:focus {
  background-color: #61b1d3;
}

#moneda_select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 0.85rem;
}

#moneda_select option {
  background-color: #83c6e6;
  color: #ffffff;
}

#moneda_select option:hover,
#moneda_select option:focus {
  background-color: #61b1d3;
}

#modalidad_cursada_select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 0.85rem;
}

#modalidad_cursada_select option {
  background-color: #83c6e6;
  color: #ffffff;
}

#modalidad_cursada_select option:hover,
#modalidad_cursada_select option:focus {
  background-color: #61b1d3;
}
</style>





















