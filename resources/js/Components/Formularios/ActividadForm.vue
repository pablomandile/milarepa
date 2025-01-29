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
import { ref } from 'vue';


// PrimeVue
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Textarea from 'primevue/textarea';
import SingleImageUploader from '@/Components/SingleImageUploader.vue';
import { useToast } from 'primevue/usetoast';


const props = defineProps({
  // Objeto con los campos que se bindearán en v-model
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

  // Arrays o catálogos necesarios para populates (opcional según tu diseño)
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
  programas: {
    type: Array,
    default: () => [],
  },
  metodosPago: {
    type: Array,
    default: () => [],
  },
  hospedajes: {
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
});

defineEmits(['submit']);

const imagePreview = ref(null);
const toast = useToast();

// Estado para ID's seleccionados (uno por dropdown)
const selectedDescripcionId = ref(null);
const selectedEntidadId = ref(null);
const selectedEsquemaPrecioId = ref(null);
const selectedEsquemaDescuentoId = ref(null);
const selectedStreamId = ref(null);
const selectedProgramaId = ref(null);


// Manejo del dialog genérico
const dialogVisible = ref(false);
const dialogTitle = ref('');
const detalleSeleccionado = ref(null);


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


</script>

<template>
  <Toast position="top-right" />
  <FormSection @submitted="$emit('submit')">
    <!-- Título del form -->
    <template #title>
      {{ updating ? 'Actualizar Actividad' : 'Nueva Actividad' }}
    </template>

    <template #description>
      {{ updating
         ? 'Edita la información de la actividad seleccionada.'
         : 'Completa los datos para registrar una nueva actividad.' }}
    </template>

    <!-- Formulario principal -->
    <template #form>
      <!-- Tipo de Actividad (Dropdown) -->
      <div class="w-1/2 col-span-6 sm:col-span-6">
        <InputLabel
          for="tipo_actividad_id"
          :required="true"
          class="text-indigo-400"
          value="Tipo de actividad"
        />
        <Dropdown
          id="tipo_actividad_id"
          v-model="form.tipo_actividad_id"
          :options="tiposActividad"
          optionLabel="nombre"      
          optionValue="id"
          placeholder="Seleccione tipo"
          class="w-full mt-1 border border-gray-300"
        />
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
            v-model="selectedDescripcionId"
            :options="descripciones"
            optionLabel="nombre"
            optionValue="id"
            placeholder="Seleccione Descripción"
            class="grow border border-gray-300 my-dropdown"
          />

          <!-- Botón ver -->
          <button
            @click="verDetalle('descripciones', selectedDescripcionId, 'Descripción')"
            :disabled="!selectedDescripcionId"
            class="ml-2 px-2 py-1 bg-indigo-500 rounded text-white"
            v-tooltip="'Ver la Descripción'"
          >
            <i class="pi pi-eye"></i>
          </button>

          <!-- Botón nuevo (Redirecciona al create de Descripciones) -->
          <Link
            :href="route('descripciones.create')"
            class="flex items-center justify-center bg-indigo-500 text-white px-3 py-2 rounded hover:bg-indigo-600"
            v-tooltip="'Crear nueva descripción'"
          >
            <i class="pi pi-file-plus"></i>
          </Link>
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

      <!-- Imagen (URL o File) -->
      <div class="col-span-6 sm:col-span-6">
        <InputLabel
          for="imagen"
          class="text-indigo-400 mb-2"
          value="Imagen"
          :required="false"
        />

        <div class="flex items-center gap-4">
          <!-- Componente personalizado para subir imágen -->
          <div class="flex justify-between mb-6" v-if="$page.props.user.permissions.includes('create entidades')">
            <SingleImageUploader 
              v-model:imagenId="form.imagen_id"
            />
          </div>
        <InputError :message="$page.props.errors.imagen" class="mt-2" />
        </div>
      </div>

      <!-- Fecha Inicio -->
      <div class="col-span-6 sm:col-span-3 w-8">
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
          class="w-full mt-1"
          icon="pi pi-calendar text-indigo-500 text-xl"
          inputClass="rounded-md border border-gray-300 focus:border-indigo-300 focus:ring-indigo-300" 
        />
        <InputError :message="$page.props.errors.fecha_inicio" class="mt-2" />
      </div>

      <!-- Fecha Fin -->
      <div class="col-span-6 sm:col-span-3 w-8">
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
        />
        <InputError :message="$page.props.errors.fecha_fin" class="mt-2" />
      </div>

      <!-- Fecha para descuentos -->
      <div class="col-span-6 sm:col-span-3 w-8">
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
        />
        <InputError :message="$page.props.errors.pagoAmticipado" class="mt-2" />
      </div>

      <!-- Entidad (Dropdown) -->
      <div class="col-span-6 sm:col-span-6">
        <InputLabel
          for="entidad_id"
          class="text-indigo-400"
          value="Entidad"
        />
        <div class="flex gap-2 items-center mt-1">
          <Dropdown
            id="entidad_id"
            v-model="selectedEntidadId"
            :options="entidades"
            optionLabel="nombre"
            optionValue="id"
            placeholder="Seleccione Entidad"
            class="w-full mt-1 border border-gray-300"
          />
          <!-- Botón ver -->
          <button
            @click="verDetalle('entidades', selectedEntidadId, 'Entidad')"
            :disabled="!selectedEntidadId"
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

      <!-- Disponibilidad (MultiSelect o Dropdown) -->
      <div class="col-span-6 sm:col-span-6">
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
            v-model="selectedEsquemaPrecioId"
            :options="esquema_precios"
            optionLabel="nombre"
            optionValue="id"
            placeholder="Seleccione un esquema"
            class="w-full mt-1 border border-gray-300"
          />
          <!-- Botón ver -->
          <button
            @click="verDetalle('esquema_precios', selectedEsquemaPrecioId, 'Esquema de Precios')"
            :disabled="!selectedEsquemaPrecioId"
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

      <!-- Esquema de Descuentos (Dropdown) -->
      <div class="col-span-6 sm:col-span-6">
        <InputLabel
          for="esquema_descuento_id"
          class="text-indigo-400"
          value="Esquema Descuentos"
        />
        <div class="flex gap-2 items-center mt-1">
          <Dropdown
            id="esquema_descuento_id"
            v-model="selectedEsquemaDescuentoId"
            :options="esquema_descuentos"
            optionLabel="nombre"
            optionValue="id"
            placeholder="Seleccione el esquema de desc."
            class="w-full mt-1 border border-gray-300"
          />
          <!-- Botón ver -->
          <button
            @click="verDetalle('esquema_descuentos', selectedEsquemaDescuentoId, 'Esquema de Descuentos')"
            :disabled="!selectedEsquemaDescuentoId"
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

      <!-- Link de grabación -->
      <div class="col-span-6 sm:col-span-6">
        <InputLabel
          for="link_grabacion"
          class="text-indigo-400"
          value="Link grabación"
        />
        <TextInput
          id="link_grabacion"
          v-model="form.link_grabacion"
          type="text"
          class="mt-1 block w-full"
        />
        <InputError :message="$page.props.errors.link_grabacion" class="mt-2" />
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

      <!-- Stream (Dropdown) -->
      <div class="col-span-6 sm:col-span-6 mt-4">
        <InputLabel
          for="stream_id"
          class="text-indigo-400"
          value="Stream"
        />
        <div class="flex gap-2 items-center mt-1">
          <Dropdown
            id="stream_id"
            v-model="selectedStreamId"
            :options="streams"
            optionLabel="nombre"
            optionValue="id"
            placeholder="Seleccione Stream"
            class="w-full mt-1 border border-gray-300"
          />
          <!-- Botón ver -->
          <button
            @click="verDetalle('streams', selectedStreamId, 'Stream')"
            :disabled="!selectedStreamId"
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
            v-model="selectedProgramaId"
            :options="programas"
            optionLabel="nombre"
            optionValue="id"
            placeholder="Seleccione un programa"
            class="w-full mt-1 border border-gray-300"
          />
          <!-- Botón ver -->
          <button
            @click="verDetalle('programas', selectedProgramaId, 'Programa')"
            :disabled="!selectedProgramaId"
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

      <!-- Hospedaje, Comidas, Transportes (MultiSelect) -->
      <div class="col-span-6 sm:col-span-6">
        <InputLabel
          for="hospedajes"
          class="text-indigo-400"
          value="Hospedajes disponibles"
        />
        <MultiSelect
          id="hospedajes"
          v-model="form.hospedajes_ids"
          :options="hospedajes"
          optionLabel="nombre"
          optionValue="id"
          class="w-full mt-1 border border-gray-300"
          placeholder="Seleccione hospedajes"
        />
        <InputError :message="$page.props.errors.hospedajes_ids" class="mt-2" />
      </div>

      <div class="col-span-6 sm:col-span-6">
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

      <div class="col-span-6 sm:col-span-6">
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
    </template>

    <!-- Botón de acción (Crear/Actualizar) -->
    <template #actions>
      <PrimaryButton>
        {{ updating ? 'Actualizar' : 'Crear' }}
      </PrimaryButton>
    </template>
  </FormSection>
   <!-- Dialog Genérico -->
   <Dialog
      v-model:visible="dialogVisible"
      :header="dialogTitle"
      :style="{ width: '50vw' }"
      dismissableMask
      modal
    >
    <template v-if="detalleSeleccionado">
      <!-- Aquí muestras la info según sea la data. Por ejemplo: -->
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
      <h4 class="font-semibold mt-4 mb-2">Membresías</h4>

      <ul class="space-y-2">
        <li
          v-for="(line, idx) in detalleSeleccionado.membresias"
          :key="line.id"
          class="border p-2 rounded"
        >
          <!-- Ejemplo de campos:
               line.precio, line.moneda.simbolo, line.membresia.nombre, line.membresia.entidad.abreviacion -->
          <strong class="block">
            <!-- Nombre de la Membresía -->
            {{ line.membresia ? line.membresia.nombre : '—' }}
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

  </Dialog>
</template>
