<script>
export default {
  name: 'ActividadForm',
};
</script>

<script setup>
import FormSection from '@/Components/FormSection.vue';
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import { Link } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import { computed, ref, watch, onMounted } from 'vue';


// PrimeVue
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Textarea from 'primevue/textarea';
import InputSwitch from 'primevue/inputswitch';
import SingleImageUploader from '@/Components/SingleImageUploader.vue';

const emit = defineEmits(['submit','refresh-descripciones']);

function onClickRefresh() {
  // En lugar de Inertia.reload aquÃ­, emitimos al padre
    emit('refresh-descripciones');
}

const props = defineProps({
  // Objeto con los campos que se bindearÃ¡n en v-model
  form: {
    type: Object,
    required: true,
  },
  // Indica si estamos creando o actualizando
  updating: {
    type: Boolean,
    required: true,
    default: false,
  },

  // Arrays o catÃ¡logos necesarios para populates (opcional segÃºn tu diseÃ±o)
  tiposActividad: {
    type: Array,
    default: () => [],
  },
  entidades: {
    type: Array,
    default: () => [],
  },
  descripciones: {
    type: Array,
    default: () => [],
  },
  disponibilidades: {
    type: Array,
    default: () => [],
  },
  modalidades: {
    type: Array,
    default: () => [],
  },
  esquema_precios: {
    type: Array,
    default: () => [],
  },
  esquema_descuentos: {
    type: Array,
    default: () => [],
  },
  streams: {
    type: Array,
    default: () => [],
  },
  grabaciones: {
    type: Array,
    default: () => [],
  },
  programas: {
    type: Array,
    default: () => [],
  },
  metodosPago: {
    type: Array,
    default: () => [],
  },
  botonesPago: {
    type: Array,
    default: () => [],
  },
  hospedajes: {
    type: Array,
    default: () => [],
  },
  lugaresHospedaje: {
    type: Array,
    default: () => [],
  },
  comidas: {
    type: Array,
    default: () => [],
  },
  transportes: {
    type: Array,
    default: () => [],
  },
  maestros: {
    type: Array,
    default: () => [],
  },
  coordinadores: {
    type: Array,
    default: () => [],
  },
  // Nuevo prop para ocultar el header interno cuando se renderiza desde una vista que ya muestra el tÃ­tulo
  hideHeader: {
    type: Boolean,
    default: false,
  },
  imagenPreviewUrl: {
    type: String,
    default: '',
  },
});


// Manejo del dialog genÃ©rico
const dialogVisible = ref(false);
const dialogTitle = ref('');
const detalleSeleccionado = ref(null);
const conDescuentoAnticipado = ref(false);
const ofreceGrabacion = ref(!!props.form.grabacion_id);
const ofreceHospedaje = ref(!!props.form.lugar_hospedaje_id || (Array.isArray(props.form.hospedajes_ids) && props.form.hospedajes_ids.length > 0));
const ofreceComidas = ref(Array.isArray(props.form.comidas_ids) && props.form.comidas_ids.length > 0);
const ofreceTransportes = ref(Array.isArray(props.form.transportes_ids) && props.form.transportes_ids.length > 0);
const botonPagoUnico = ref(!!props.form.botonpago_id);

/**
 * verDetalle(arrayName, id, title):
 *  1) Toma el array (ej 'descripciones', 'entidades') 
 *  2) Busca el objeto con ID 
 *  3) Establece dialogTitle
 *  4) Muestra el dialog
 */
 function verDetalle(arrayName, id, title) {
  if (!id) return;
  dialogTitle.value = title;

  // Acceder al array por su nombre
  const arrayData = props[arrayName];
  if (!arrayData) return;

  // Buscar el objeto con el ID
  detalleSeleccionado.value = arrayData.find(item => item.id === id);

  // Mostrar dialog
  dialogVisible.value = true;
  
}

function formatDatetime(date) {
  if (!date) return null;
  
  // Si es una instancia de Date
  if (date instanceof Date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
  }
  
  // Si es string, normalizar formato
  if (typeof date === 'string') {
    const dateObj = new Date(date);
    const year = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const day = String(dateObj.getDate()).padStart(2, '0');
    const hours = String(dateObj.getHours()).padStart(2, '0');
    const minutes = String(dateObj.getMinutes()).padStart(2, '0');
    const seconds = String(dateObj.getSeconds()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
  }
  
  return date;
}

watch(
  () => ofreceGrabacion.value,
  (value) => {
    if (!value) {
      props.form.grabacion_id = null;
    }
  }
);

watch(
  () => ofreceHospedaje.value,
  (value) => {
    if (!value) {
      props.form.lugar_hospedaje_id = null;
      props.form.hospedajes_ids = [];
    }
  }
);

watch(
  () => ofreceComidas.value,
  (value) => {
    if (!value) {
      props.form.comidas_ids = [];
    }
  }
);

watch(
  () => ofreceTransportes.value,
  (value) => {
    if (!value) {
      props.form.transportes_ids = [];
    }
  }
);

watch(
  () => botonPagoUnico.value,
  (value) => {
    if (!value) {
      props.form.botonpago_id = null;
    }
  }
);

function submitForm() {
  // Formatear las fechas antes de enviar
  if (props.form.fecha_inicio) {
    props.form.fecha_inicio = formatDatetime(props.form.fecha_inicio);
  }
  if (props.form.fecha_fin) {
    props.form.fecha_fin = formatDatetime(props.form.fecha_fin);
  }
  if (props.form.pagoAmticipado) {
    props.form.pagoAmticipado = formatDatetime(props.form.pagoAmticipado);
  }
  
  emit('submit');
}

const filteredHospedajes = computed(() => {
  if (!props.form?.lugar_hospedaje_id) {
    return props.hospedajes;
  }
  return props.hospedajes.filter(
    (hospedaje) => hospedaje.lugar_hospedaje_id === props.form.lugar_hospedaje_id
  );
});

function parseToDate(value) {
  if (!value) return null;
  if (value instanceof Date) return value;
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function normalizeDateFields() {
  props.form.fecha_inicio = parseToDate(props.form.fecha_inicio);
  props.form.fecha_fin = parseToDate(props.form.fecha_fin);
  props.form.pagoAmticipado = parseToDate(props.form.pagoAmticipado);
}

onMounted(() => {
  normalizeDateFields();
});

watch(
  () => [props.form?.fecha_inicio, props.form?.fecha_fin, props.form?.pagoAmticipado],
  () => {
    normalizeDateFields();
  }
);

function syncHospedajesSeleccionados() {
  if (!Array.isArray(props.form?.hospedajes_ids)) return;
  const allowedIds = new Set(filteredHospedajes.value.map((hospedaje) => hospedaje.id));
  props.form.hospedajes_ids = props.form.hospedajes_ids.filter((id) => allowedIds.has(id));
}

watch(
  () => props.form?.lugar_hospedaje_id,
  () => {
    syncHospedajesSeleccionados();
  }
);

watch(
  () => props.hospedajes,
  () => {
    syncHospedajesSeleccionados();
  }
);

watch(
  () => [props.form?.esquema_descuento_id, props.form?.pagoAmticipado],
  () => {
    conDescuentoAnticipado.value = !!props.form?.esquema_descuento_id || !!props.form?.pagoAmticipado;
  },
  { immediate: true }
);

</script>

<template>
  <Toast position="top-right" />
  <!-- pasar no-aside basado en hideHeader -->
  <FormSection :no-aside="hideHeader" @submitted="submitForm">
    <template #title></template>
    <template #description></template>

    <!-- Formulario principal -->
    <template #form>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Header que ocupa las 3 columnas (se muestra solo si hideHeader === false) -->
        <div v-if="!hideHeader" class="md:col-span-3">
          <h2 class="text-2xl font-semibold text-indigo-600">
            {{ updating ? 'Actualizar Actividad' : 'Nueva Actividad' }}
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            {{ updating
               ? 'Edita la información de la actividad seleccionada.'
               : 'Completa los datos para registrar una nueva actividad.' }}
          </p>
        </div>

        <!-- Tipo de Actividad (Dropdown) -->
        <div class="w-1/2 col-span-6 sm:col-span-6">
          <InputLabel
            for="tipo_actividad_id"
            :required="true"
            class="text-indigo-400"
            value="Tipo de actividad"
          />
          <div class="flex gap-2 items-center mt-1">
            <Dropdown
              id="tipo_actividad_id"
              v-model="form.tipo_actividad_id"
              :options="tiposActividad"
              optionLabel="nombre"      
              optionValue="id"
              placeholder="Seleccione tipo"
              class="grow border border-gray-300"
            />
            
            <!-- Botón nuevo (Redirecciona al create de Tipos de Actividad) -->
            <Link
              :href="route('tiposactividad.create')"
              class="ml-2 px-2 py-1 bg-indigo-500 rounded text-white"
              v-tooltip="'Crear nuevo tipo de actividad'"
            >
              <i class="pi pi-file-plus"></i>
            </Link>
          </div>
          <InputError :message="$page.props.errors.tipo_actividad_id" class="mt-2" />
        </div>

        <!-- Nombre -->
        <div class="w-full col-span-6 sm:col-span-6">
          <InputLabel
            for="nombre"
            class="text-indigo-400"
            value="Nombre"
            :required="true"
          />
          <TextInput
            id="nombre"
            v-model="form.nombre"
            type="text"
            autocomplete="off"
            class="mt-1 block w-full"
          />
          <InputError :message="$page.props.errors.nombre" class="mt-2" />
        </div>

        <!-- Descripción -->
        <div class="col-span-6 sm:col-span-6">
          <InputLabel
            for="descripcion_id"
            class="text-indigo-400"
            value="Descripción"
            :required="false"
          />
           <!-- Contenedor para el Dropdown y el Botón + -->
          <div class="flex gap-2 items-center mt-1">
            <!-- Dropdown -->
            <Dropdown
              id="descripcion_id"
              v-model="form.descripcion_id"
              :options="descripciones"
              optionLabel="nombre"
              optionValue="id"
              placeholder="Seleccione Descripción"
              class="grow border border-gray-300 my-dropdown"
            />

            <!-- Botón ver -->
            <button
              type="button"
              @click="verDetalle('descripciones', form.descripcion_id, 'Descripción')"
              :disabled="!form.descripcion_id"
              class="ml-2 px-2 py-1 bg-indigo-500 rounded text-white"
              v-tooltip="'Ver la Descripción'"
            >
              <i class="pi pi-eye"></i>
            </button>

            <!-- Botón nuevo (Redirecciona al create de Descripciones) -->
            <Link
              :href="route('descripciones.create')"
              class="ml-2 px-2 py-1 bg-indigo-500 rounded text-white"
              v-tooltip="'Crear nueva descripción'"
            >
              <i class="pi pi-file-plus"></i>
            </Link>

            <button type="button" class="ml-2 px-2 py-1 bg-indigo-500 rounded text-white" @click="onClickRefresh" v-tooltip="'Refrescar'">
              <i class="pi pi-refresh "></i>
            </button>
          </div>
          <InputError :message="$page.props.errors.descripcion_id" class="mt-2" />
        </div>

        <!-- Observaciones -->
        <div class="w-full col-span-6 sm:col-span-6">
          <InputLabel
            for="observaciones"
            class="text-indigo-400"
            value="Observaciones"
            :required="false"
          />
          <Textarea
            id="observaciones"
            v-model="form.observaciones"
            type="text"
            autocomplete="off"
            class="mt-1 block w-full border border-gray-300 rounded"
          />
          <InputError :message="$page.props.errors.observaciones" class="mt-2" />
        </div>

       <!-- Maestros (MultiSelect) -->
       <div class="col-span-6 sm:col-span-6">
          <InputLabel
            for="maestros"
            class="text-indigo-400"
            value="Maestro/s"
          />
          <MultiSelect
            id="maestros"
            v-model="form.maestros_ids"
            :options="maestros"
            optionLabel="nombre"
            optionValue="id"
            class="w-full mt-1 border border-gray-300"
            placeholder="Seleccione maestro/s"
          />
          <InputError :message="$page.props.errors.maestros_ids" class="mt-2" />
        </div>

        <!-- Coordinadores (MultiSelect) -->
        <div class="col-span-6 sm:col-span-6">
          <InputLabel
            for="coordinadores"
            class="text-indigo-400"
            value="Coordinador/es"
          />
          <MultiSelect
            id="coordinadores"
            v-model="form.coordinadores_ids"
            :options="coordinadores"
            optionLabel="nombre"
            optionValue="id"
            class="w-full mt-1 border border-gray-300"
            placeholder="Seleccione coordinador/es"
          />
          <InputError :message="$page.props.errors.coordinadores_ids" class="mt-2" />
        </div>

        <!-- Fecha Inicio -->
        <div class="col-span-6 sm:col-span-2">
          <InputLabel
            for="fecha_inicio"
            class="text-indigo-400"
            value="Fecha Inicio"
            :required="true"
          />
          <Calendar
            id="fecha_inicio"
            v-model="form.fecha_inicio"
            dateFormat="dd/mm/yy"
            :showIcon="true"
            showTime
            hourFormat="24"
            :stepMinute="1"
            class="w-full mt-1"
            icon="pi pi-calendar text-indigo-500 text-xl"
            inputClass="rounded-md border border-gray-300 focus:border-indigo-300 focus:ring-indigo-300"
            appendTo="body"
            @update:modelValue="form.fecha_inicio = $event"
          />
          <InputError :message="$page.props.errors.fecha_inicio" class="mt-2" />
        </div>

        <!-- Fecha Fin -->
        <div class="col-span-6 sm:col-span-2">
          <InputLabel
            for="fecha_fin"
            class="text-indigo-400"
            value="Fecha Fin"
            :required="true"
          />
          <Calendar
            id="fecha_fin"
            v-model="form.fecha_fin"
            dateFormat="dd/mm/yy"
            showTime  
            :showIcon="true"
            hourFormat="24"
            class="w-full mt-1"
            icon="pi pi-calendar text-indigo-500 text-xl"
            inputClass="rounded-md border border-gray-300 focus:border-indigo-300 focus:ring-indigo-300"
            appendTo="body"
          />
          <InputError :message="$page.props.errors.fecha_fin" class="mt-2" />
        </div>

        <!-- Entidad (Dropdown) -->
        <div class="col-span-6 sm:col-span-6">
          <InputLabel
            for="entidad_id"
            class="text-indigo-400"
            value="Entidad"
            :required="true"
          />
          <div class="flex gap-2 items-center mt-1">
            <Dropdown
              id="entidad_id"
              v-model="form.entidad_id"
              :options="entidades"
              optionLabel="nombre"
              optionValue="id"
              placeholder="Seleccione Entidad"
              class="w-full mt-1 border border-gray-300"
            />
            <!-- Botón ver -->
            <button
              type="button"
              @click="verDetalle('entidades', form.entidad_id, 'Entidad')"
              :disabled="!form.entidad_id"
              class="ml-2 px-2 py-1 bg-indigo-500 rounded text-white"
              v-tooltip="'Ver la Entidad'"
            >
              <i class="pi pi-eye"></i>
            </button>

            <!-- Botón nuevo (Redirecciona al create) -->
            <Link
              :href="route('entidades.create')"
              class="flex items-center justify-center bg-indigo-500 text-white px-3 py-2 rounded hover:bg-indigo-600"
              v-tooltip="'Crear nueva Entidad'"
            >
              <i class="pi pi-file-plus"></i>
            </Link>
          </div>
          <InputError :message="$page.props.errors.entidad_id" class="mt-2" />
        </div>

        <!-- Modalidad (Dropdown) -->
        <div class="col-span-6 sm:col-span-6">
          <InputLabel
            for="modalidad_id"
            class="text-indigo-400"
            value="Modalidad"
          />
          <Dropdown
            id="modalidad_id"
            v-model="form.modalidad_id"
            :options="modalidades"
            optionLabel="nombre"
            optionValue="id"
            placeholder="Seleccione la modalidad"
            class="w-full mt-1 border border-gray-300"
          />
          <InputError :message="$page.props.errors.modalidad_id" class="mt-2" />
        </div>

        <!-- Disponibilidad (MultiSelect o Dropdown) -->
        <div v-if="form.modalidad_id !== 1" class="col-span-6 sm:col-span-6">
          <InputLabel
            for="disponibilidad_id"
            class="text-indigo-400"
            value="Disponibilidad"
          />
          <Dropdown
            id="disponibilidad_id"
            v-model="form.disponibilidad_id"
            :options="disponibilidades"
            optionLabel="descripcion"
            optionValue="id"
            placeholder="Seleccione la Disponibilidad"
            class="w-full mt-1 border border-gray-300"
          />
          <InputError :message="$page.props.errors.disponibilidad_id" class="mt-2" />
        </div>

        <!-- Esquema de Precios (Dropdown) -->
        <div class="col-span-6 sm:col-span-6">
          <InputLabel
            for="esquema_precio_id"
            class="text-indigo-400"
            value="Esquema Precios"
          />
          <div class="flex gap-2 items-center mt-1">
            <Dropdown
              id="esquema_precio_id"
              v-model="form.esquema_precio_id"
              :options="esquema_precios"
              optionLabel="nombre"
              optionValue="id"
              placeholder="Seleccione un esquema"
              class="w-full mt-1 border border-gray-300"
            />
            <!-- Botón ver -->
            <button
              type="button"
              @click="verDetalle('esquema_precios', form.esquema_precio_id, 'Esquema de Precios')"
              :disabled="!form.esquema_precio_id"
              class="ml-2 px-2 py-1 bg-indigo-500 rounded text-white"
              v-tooltip="'Ver el Esquema'"
            >
              <i class="pi pi-eye"></i>
            </button>

            <!-- Botón nuevo (Redirecciona al create) -->
            <Link
              :href="route('esquemaprecios.create')"
              class="flex items-center justify-center bg-indigo-500 text-white px-3 py-2 rounded hover:bg-indigo-600"
              v-tooltip="'Crear nuevo Esquema'"
            >
              <i class="pi pi-file-plus"></i>
            </Link>
          </div>
          <InputError :message="$page.props.errors.esquema_precio_id" class="mt-2" />
        </div>

        <!-- Switch: descuento por pago anticipado -->
        <div class="col-span-6 sm:col-span-6">
          <InputLabel
            for="con_descuento_anticipado"
            class="text-indigo-400"
            value="Con descuento por pago anticipado"
          />
          <div class="flex items-center gap-3 mt-2">
            <InputSwitch
              id="con_descuento_anticipado"
              v-model="conDescuentoAnticipado"
            />
            <span class="text-sm text-gray-600">
              {{ conDescuentoAnticipado ? 'Activo' : 'Inactivo' }}
            </span>
          </div>
        </div>

        <!-- Fecha para descuentos -->
        <div v-if="conDescuentoAnticipado" class="col-span-6 sm:col-span-2">
          <InputLabel
            for="pagoAmticipado"
            class="text-indigo-400"
            value="Fecha pago anticipado"
            inputClass="text-indigo-500"
          />
          <Calendar
            id="pagoAmticipado"
            v-model="form.pagoAmticipado"
            dateFormat="dd/mm/yy"
            showTime  
            hourFormat="24"
            class="w-full mt-1"
            :showIcon="true"
            icon="pi pi-calendar text-indigo-500 text-xl"
            inputClass="rounded-md border border-gray-300 focus:border-indigo-300 focus:ring-indigo-300"
            appendTo="body"
          />
          <InputError :message="$page.props.errors.pagoAmticipado" class="mt-2" />
        </div>

        <!-- Esquema de Descuentos (Dropdown) -->
        <div v-if="conDescuentoAnticipado" class="col-span-6 sm:col-span-6">
          <InputLabel
            for="esquema_descuento_id"
            class="text-indigo-400"
            value="Esquema Descuentos"
          />
          <div class="flex gap-2 items-center mt-1">
            <Dropdown
              id="esquema_descuento_id"
              v-model="form.esquema_descuento_id"
              :options="esquema_descuentos"
              optionLabel="nombre"
              optionValue="id"
              placeholder="Seleccione el esquema de desc."
              class="w-full mt-1 border border-gray-300"
            />
            <!-- Botón ver -->
            <button
              type="button"
              @click="verDetalle('esquema_descuentos', form.esquema_descuento_id, 'Esquema de Descuentos')"
              :disabled="!form.esquema_descuento_id"
              class="ml-2 px-2 py-1 bg-indigo-500 rounded text-white"
              v-tooltip="'Ver el Esquema'"
            >
              <i class="pi pi-eye"></i>
            </button>
            <!-- Botón nuevo (Redirecciona al create) -->
            <Link
              :href="route('esquemadescuentos.create')"
              class="flex items-center justify-center bg-indigo-500 text-white px-3 py-2 rounded hover:bg-indigo-600"
              v-tooltip="'Crear nuevo Esquema'"
            >
            <i class="pi pi-file-plus"></i>
            </Link>
          </div>
        </div>

        



        <!-- Web actividad -->
        <div class="col-span-6 sm:col-span-6">
          <InputLabel
            for="web_actividad"
            class="text-indigo-400"
            value="Web actividad"
          />
          <TextInput
            id="web_actividad"
            v-model="form.web_actividad"
            type="text"
            class="mt-1 block w-full"
          />
          <InputError :message="$page.props.errors.web_actividad" class="mt-2" />
        </div>

        <!-- Programa (Dropdown) -->
        <div class="col-span-6 sm:col-span-6">
          <InputLabel
            for="programa_id"
            class="text-indigo-400"
            value="Programa"
          />
          <div class="flex gap-2 items-center mt-1">
            <Dropdown
              id="programa_id"
              v-model="form.programa_id"
              :options="programas"
              optionLabel="nombre"
              optionValue="id"
              placeholder="Seleccione un programa"
              class="w-full mt-1 border border-gray-300"
            />
            <!-- Botón ver -->
            <button
              type="button"
              @click="verDetalle('programas', form.programa_id, 'Programa')"
              :disabled="!form.programa_id"
              class="ml-2 px-2 py-1 bg-indigo-500 rounded text-white"
              v-tooltip="'Ver el Programa'"
            >
              <i class="pi pi-eye"></i>
            </button>

            <!-- Botón nuevo (Redirecciona al create) -->
            <Link
              :href="route('programas.create')"
              class="flex items-center justify-center bg-indigo-500 text-white px-3 py-2 rounded hover:bg-indigo-600"
              v-tooltip="'Crear nuevo Programa'"
            >
              <i class="pi pi-file-plus"></i>
            </Link>
          </div>
          <InputError :message="$page.props.errors.programa_id" class="mt-2" />
        </div>

        <!-- Métodos de pago (MultiSelect) -->
        <div class="col-span-6 sm:col-span-6">
          <InputLabel
            for="metodos_pago"
            class="text-indigo-400"
            value="Métodos de Pago"
          />
          <MultiSelect
            id="metodos_pago"
            v-model="form.metodos_pago_ids"
            :options="metodosPago"
            optionLabel="nombre"
            optionValue="id"
            class="w-full mt-1 border border-gray-300"
            placeholder="Seleccione métodos"
          />
          <InputError :message="$page.props.errors.metodos_pago_ids" class="mt-2" />
        </div>

         <!-- Grabación / Hospedaje / Comidas / Transportes -->
         <div class="col-span-6 sm:col-span-6 mt-1 grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <InputLabel
              for="ofrece_grabacion"
              class="text-indigo-400"
              value="Ofrece Grabación"
            />
            <div class="mt-1">
              <InputSwitch id="ofrece_grabacion" v-model="ofreceGrabacion" />
            </div>
          </div>
          <div>
            <InputLabel
              for="ofrece_hospedaje"
              class="text-indigo-400"
              value="Ofrece Hospedaje"
            />
            <div class="mt-1">
              <InputSwitch id="ofrece_hospedaje" v-model="ofreceHospedaje" />
            </div>
          </div>
          <div>
            <InputLabel
              for="ofrece_comidas"
              class="text-indigo-400"
              value="Ofrece Comidas"
            />
            <div class="mt-1">
              <InputSwitch id="ofrece_comidas" v-model="ofreceComidas" />
            </div>
          </div>
          <div>
            <InputLabel
              for="ofrece_transportes"
              class="text-indigo-400"
              value="Ofrece Transportes"
            />
            <div class="mt-1">
              <InputSwitch id="ofrece_transportes" v-model="ofreceTransportes" />
            </div>
          </div>

          <div v-if="ofreceGrabacion">
            <InputLabel
              for="grabacion_id"
              class="text-indigo-400"
              value="Grabaciones"
            />
            <div class="flex gap-2 items-center mt-1">
              <Dropdown
                id="grabacion_id"
                v-model="form.grabacion_id"
                :options="grabaciones"
                optionLabel="nombre"
                optionValue="id"
                placeholder="Seleccione Grabación"
                class="w-full mt-1 border border-gray-300"
              />
              <button
                type="button"
                @click="verDetalle('grabaciones', form.grabacion_id, 'Grabación')"
                :disabled="!form.grabacion_id"
                class="ml-2 px-2 py-1 bg-indigo-500 rounded text-white"
                v-tooltip="'Ver la Grabación'"
              >
                <i class="pi pi-eye"></i>
              </button>
              <Link
                :href="route('grabaciones.create')"
                class="flex items-center justify-center bg-indigo-500 text-white px-3 py-2 rounded hover:bg-indigo-600"
                v-tooltip="'Crear nueva Grabación'"
              >
                <i class="pi pi-file-plus"></i>
              </Link>
            </div>
            <InputError :message="$page.props.errors.grabacion_id" class="mt-2" />
          </div>

        </div>

        <!-- Stream (Dropdown) -->
        <div v-if="form.modalidad_id !== 1" class="col-span-6 sm:col-span-6 mt-4">
          <InputLabel
            for="stream_id"
            class="text-indigo-400"
            value="Stream"
          />
          <div class="flex gap-2 items-center mt-1">
            <Dropdown
              id="stream_id"
              v-model="form.stream_id"
              :options="streams"
              optionLabel="nombre"
              optionValue="id"
              placeholder="Seleccione Stream"
              class="w-full mt-1 border border-gray-300"
            />
            <!-- Botón ver -->
            <button
              type="button"
              @click="verDetalle('streams', form.stream_id, 'Stream')"
              :disabled="!form.stream_id"
              class="ml-2 px-2 py-1 bg-indigo-500 rounded text-white"
              v-tooltip="'Ver el Stream'"
            >
              <i class="pi pi-eye"></i>
            </button>

            <!-- Botón nuevo (Redirecciona al create) -->
            <Link
              :href="route('streams.create')"
              class="flex items-center justify-center bg-indigo-500 text-white px-3 py-2 rounded hover:bg-indigo-600"
              v-tooltip="'Crear nuevo Stream'"
            >
              <i class="pi pi-file-plus"></i>
            </Link>
          </div>
          <InputError :message="$page.props.errors.stream_id" class="mt-2" />
        </div>

        <!-- Hospedaje, Comidas, Transportes (MultiSelect) -->
        <div v-if="ofreceHospedaje" class="col-span-6 sm:col-span-6">
          <InputLabel
            for="lugar_hospedaje_id"
            class="text-indigo-400"
            value="Lugar de Hospedaje"
          />
          <Dropdown
            id="lugar_hospedaje_id"
            v-model="form.lugar_hospedaje_id"
            :options="lugaresHospedaje"
            optionLabel="nombre"
            optionValue="id"
            placeholder="Seleccione un lugar"
            class="w-full mt-1 border border-gray-300"
          />
          <InputError :message="$page.props.errors.lugar_hospedaje_id" class="mt-2" />
        </div>

        <div v-if="ofreceHospedaje" class="col-span-6 sm:col-span-6">
          <InputLabel
            for="hospedajes"
            class="text-indigo-400"
            value="Hospedajes disponibles"
          />
          <MultiSelect
            id="hospedajes"
            v-model="form.hospedajes_ids"
            :options="filteredHospedajes"
            optionLabel="nombre"
            optionValue="id"
            class="w-full mt-1 border border-gray-300"
            placeholder="Seleccione hospedajes"
          />
          <InputError :message="$page.props.errors.hospedajes_ids" class="mt-2" />
        </div>

        <div v-if="ofreceComidas" class="col-span-6 sm:col-span-6">
          <InputLabel
            for="comidas"
            class="text-indigo-400"
            value="Comidas"
          />
          <MultiSelect
            id="comidas"
            v-model="form.comidas_ids"
            :options="comidas"
            optionLabel="nombre"
            optionValue="id"
            class="w-full mt-1 border border-gray-300"
            placeholder="Seleccione comidas"
          />
          <InputError :message="$page.props.errors.comidas_ids" class="mt-2" />
        </div>

        <div v-if="ofreceTransportes" class="col-span-6 sm:col-span-6">
          <InputLabel
            for="transportes"
            class="text-indigo-400"
            value="Transportes"
          />
          <MultiSelect
            id="transportes"
            v-model="form.transportes_ids"
            :options="transportes"
            optionLabel="descripcion"
            optionValue="id"
            class="w-full mt-1 border border-gray-300"
            placeholder="Seleccione transportes"
          />
          <InputError :message="$page.props.errors.transportes_ids" class="mt-2" />
        </div>

        <!-- Imagen (URL o File) -->
        <div class="col-span-6 sm:col-span-6">
          <InputLabel
            for="imagen"
            class="text-indigo-400 mb-2"
            value="Imagen"
            :required="false"
          />

          <div class="flex items-start gap-4">
            <!-- Componente personalizado para subir imÃ¡gen -->
            <div class="flex justify-between" v-if="$page.props.user.permissions.includes('create entidades')">
              <SingleImageUploader 
                v-model:imagenId="form.imagen_id"
              />
            </div>
            <div v-if="imagenPreviewUrl" class="flex items-center gap-2">
              <img
                :src="imagenPreviewUrl"
                alt="Imagen actual"
                class="h-16 w-16 mt-0 pt-0 rounded border border-gray-200 object-cover"
              />
              <span class="text-xs text-gray-500">Actual</span>
            </div>
          <InputError :message="$page.props.errors.imagen" class="mt-2" />
          </div>
        </div>

        <!-- Estado (Activa/Inactiva) -->
        <div class="col-span-6 sm:col-span-6">
          <InputLabel
            for="estado"
            class="text-indigo-400"
            value="Estado"
            :required="true"
          />
          <Dropdown
            id="estado"
            v-model="form.estado"
            :options="[
              { label: 'Activa', value: true },
              { label: 'Inactiva', value: false }
            ]"
            optionLabel="label"
            optionValue="value"
            placeholder="Seleccione el estado"
            class="w-full mt-1 border border-gray-300"
          />
          <InputError :message="$page.props.errors.estado" class="mt-2" />
        </div>

        <!-- Botón de pago único -->
        <div class="col-span-6 sm:col-span-6">
          <InputLabel
            for="boton_pago_unico"
            class="text-indigo-400"
            value="Botón de pago único"
          />
          <div class="flex items-center gap-3 mt-2">
            <InputSwitch
              id="boton_pago_unico"
              v-model="botonPagoUnico"
            />
            <span class="text-sm text-gray-600">
              {{ botonPagoUnico ? 'Activo' : 'Inactivo' }}
            </span>
          </div>
        </div>

        <div v-if="botonPagoUnico" class="col-span-6 sm:col-span-6">
          <InputLabel
            for="botonpago_id"
            class="text-indigo-400"
            value="Botón de pago"
          />
          <Dropdown
            id="botonpago_id"
            v-model="form.botonpago_id"
            :options="botonesPago"
            optionLabel="nombre"
            optionValue="id"
            placeholder="Seleccione un botón de pago"
            class="w-full mt-1 border border-gray-300"
            showClear
          />
          <InputError :message="$page.props.errors.botonpago_id" class="mt-2" />
        </div>
      </div>
    </template>

    <!-- Botón de acción (Crear/Actualizar) -->
    <template #actions>
      <PrimaryButton>
        {{ updating ? 'Actualizar' : 'Crear' }}
      </PrimaryButton>
    </template>
  </FormSection>
  <!-- ... -->
   <!-- Dialog GenÃ©rico -->
   <Dialog
      v-model:visible="dialogVisible"
      :header="dialogTitle"
      :style="{ width: '50vw' }"
      dismissableMask
      modal
    >
    <template v-if="detalleSeleccionado">
      <!-- AquÃ­ muestras la info segÃºn sea la data. Por ejemplo: -->
      <h3 class="text-xl font-bold mb-2">{{ detalleSeleccionado.nombre }}</h3>
      <p class="text-sm text-gray-600 whitespace-pre-wrap">
        <!-- Muestra 'contenido' o 'descripcion' o 'info', depende de tu objeto -->
        {{ detalleSeleccionado.contenido || detalleSeleccionado.descripcion }}
      </p>
      <p v-if="detalleSeleccionado.direccion">
        <strong>Dirección:</strong> {{ detalleSeleccionado.direccion }}
      </p>
      <p v-if="detalleSeleccionado.programa">
        <strong>Programa:</strong> {{ detalleSeleccionado.programa }}
      </p>
    </template>
    <template v-else>
      <p>No se encontró la información solicitada.</p>
    </template>

    <!-- Comprobamos si hay 'membresias' -->
    <div v-if="detalleSeleccionado.membresias && detalleSeleccionado.membresias.length > 0">
      <h4 class="font-semibold mt-4 mb-2">MembresÃ­as</h4>

      <ul class="space-y-2">
        <li
          v-for="(line, idx) in detalleSeleccionado.membresias"
          :key="line.id"
          class="border p-2 rounded"
        >
          <!-- Ejemplo de campos:
               line.precio, line.moneda.simbolo, line.membresia.nombre, line.membresia.entidad.abreviacion -->
          <strong class="block">
            <!-- Nombre de la MembresÃ­a -->
            {{ line.membresia ? line.membresia.nombre : 'â€”' }}
          </strong>

          <!-- Precio + Moneda -->
          <span v-if="line.moneda">
            {{ line.moneda.simbolo }} {{ line.precio }}
          </span>
          <span v-else>
            ${{ line.precio }}
          </span>

          <!-- Entidad abreviación, si aplica -->
          <span v-if="line.membresia && line.membresia.entidad">
            ({{ line.membresia.entidad.abreviacion }})
          </span>
        </li>
      </ul>
    </div>

    <div v-if="detalleSeleccionado.links && detalleSeleccionado.links.length > 0">
      <h4 class="font-semibold mt-4 mb-2">Links</h4>

      <ul class="space-y-2">
        <li
          v-for="(line, idx) in detalleSeleccionado.links"
          :key="line.id"
          class="border p-2 rounded"
        >
          <strong class="block">
            {{ line.nombre }}
          </strong>

          <span v-if="line.link">
            {{ line.link }}
          </span>
        </li>
      </ul>
    </div>

    <div v-if="detalleSeleccionado.linksgrabacion && detalleSeleccionado.linksgrabacion.length > 0">
      <h4 class="font-semibold mt-4 mb-2">Links</h4>

      <ul class="space-y-2">
        <li
          v-for="(line, idx) in detalleSeleccionado.linksgrabacion"
          :key="line.id"
          class="border p-2 rounded"
        >
          <strong class="block">
            {{ line.nombre }}
          </strong>

          <span v-if="line.link">
            {{ line.link }}
          </span>
        </li>
      </ul>
    </div>
  </Dialog>
</template>
