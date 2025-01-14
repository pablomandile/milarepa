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

// PrimeVue
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';

defineProps({
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
</script>

<template>
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
      <div class="col-span-6 sm:col-span-6">
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
      <div class="col-span-6 sm:col-span-6">
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
        <Dropdown
          id="descripcion_id"
          v-model="form.descripcion_id"
          :options="descripciones"
          optionLabel="nombre"      
          optionValue="id"
          placeholder="Seleccione Descripción"
          class="w-full mt-1 border border-gray-300"
        />
        <InputError :message="$page.props.errors.descripcion_id" class="mt-2" />
      </div>

      <!-- Imagen (URL o File) -->
      <div class="col-span-6 sm:col-span-6">
        <InputLabel
          for="imagen"
          class="text-indigo-400"
          value="Imagen"
          :required="false"
        />
        <TextInput
          id="imagen"
          v-model="form.imagen"
          type="text"
          placeholder="URL o ruta de imagen"
          class="mt-1 block w-full"
        />
        <!-- O un componente file upload. -->
        <InputError :message="$page.props.errors.imagen" class="mt-2" />
      </div>

      <!-- Fecha Inicio -->
      <div class="col-span-6 sm:col-span-3">
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
          class="w-full mt-1 border border-gray-300"
        />
        <InputError :message="$page.props.errors.fecha_inicio" class="mt-2" />
      </div>

      <!-- Fecha Fin -->
      <div class="col-span-6 sm:col-span-3">
        <InputLabel
          for="fecha_fin"
          class="text-indigo-400"
          value="Fecha Fin"
        />
        <Calendar
          id="fecha_fin"
          v-model="form.fecha_fin"
          dateFormat="dd/mm/yy"
          class="w-full mt-1 border border-gray-300"
          :showIcon="true"
        />
        <InputError :message="$page.props.errors.fecha_fin" class="mt-2" />
      </div>

      <!-- Entidad (Dropdown) -->
      <div class="col-span-6 sm:col-span-6">
        <InputLabel
          for="entidad_id"
          class="text-indigo-400"
          value="Entidad"
        />
        <Dropdown
          id="entidad_id"
          v-model="form.entidad_id"
          :options="entidades"
          optionLabel="nombre"
          optionValue="id"
          placeholder="Seleccione Entidad"
          class="w-full mt-1 border border-gray-300"
        />
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
        <Dropdown
          id="esquema_precio_id"
          v-model="form.esquema_precio_id"
          :options="esquema_precios"
          optionLabel="nombre"
          optionValue="id"
          placeholder="Seleccione un esquema"
          class="w-full mt-1 border border-gray-300"
        />
        <InputError :message="$page.props.errors.esquema_precio_id" class="mt-2" />
      </div>

      <!-- Esquema de Descuentos (Dropdown) -->
      <div class="col-span-6 sm:col-span-6">
        <InputLabel
          for="esquema_descuento_id"
          class="text-indigo-400"
          value="Esquema Descuentos"
        />
        <Dropdown
          id="esquema_descuento_id"
          v-model="form.esquema_descuento_id"
          :options="esquema_descuentos"
          optionLabel="nombre"
          optionValue="id"
          placeholder="Seleccione el esquema de desc."
          class="w-full mt-1 border border-gray-300"
        />
        <InputError :message="$page.props.errors.esquema_descuento_id" class="mt-2" />
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
        <Dropdown
          id="stream_id"
          v-model="form.stream_id"
          :options="streams"
          optionLabel="nombre"
          optionValue="id"
          placeholder="Seleccione Stream"
          class="w-full mt-1 border border-gray-300"
        />
        <InputError :message="$page.props.errors.stream_id" class="mt-2" />
      </div>

      <!-- Programa (Dropdown) -->
      <div class="col-span-6 sm:col-span-6">
        <InputLabel
          for="programa_id"
          class="text-indigo-400"
          value="Programa"
        />
        <Dropdown
          id="programa_id"
          v-model="form.programa_id"
          :options="programas"
          optionLabel="nombre"
          optionValue="id"
          placeholder="Seleccione un programa"
          class="w-full mt-1 border border-gray-300"
        />
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
</template>
