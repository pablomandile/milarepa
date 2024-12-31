<script>
    export default {
        name: 'TicketsForm'
    }
</script>

<script setup>
import FormSection from '@/Components/FormSection.vue'
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import { watch, ref, computed } from 'vue';



const props = defineProps({
        form: {
            type: Object,
            required: true
        },

        updating: {
            type: Boolean,
            required: true,
            default: false
        },

        usuariosResponsables: {
            type: Array,
            default: () => []
        },

        estados: {
            type: Array,
            default: () => []
        }
});

defineEmits(['submit']);

// Variable booleana para deshabilitar el dropdown de estado
const disableEstadoDropdown = ref(false);

// Identificar el estado "Pendiente" y "Asignado".
const idPendiente = computed(() => {
  const pendiente = props.estados.find((e) => e.estado === 'Pendiente');
  return pendiente ? pendiente.id : null;
});

const idAsignado = computed(() => {
  const asignado = props.estados.find((e) => e.estado === 'Asignado');
  return asignado ? asignado.id : null;
});

// Vigilar form.responsable_id
watch(
  () => props.form.responsable_id,
  (newVal) => {
    // Si newVal es null => "Sin responsable"
    if (newVal === null) {
      // Fijar estado a "Pendiente"
      if (idPendiente.value) {
        props.form.estado_ticket_id = idPendiente.value;
      }
      disableEstadoDropdown.value = true;
    } else {
      // Fijar estado a "Asignado"
      if (idAsignado.value) {
        props.form.estado_ticket_id = idAsignado.value;
      }
      disableEstadoDropdown.value = false;
    }
  },
  { immediate: true }
);


</script>

<template>
    <FormSection @submitted="$emit('submit')">
        <template #title>
            {{ updating ? 'Actualizar Ticket' : 'Nuevo Ticket' }}
        </template>
        <template #description>
            {{ updating ? 'Actualizando el Ticket seleccionado' : 'Agregando un nuevo Ticket. Recibirás ayuda a la brevedad.' }}
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="asunto" value="Asunto" :required="true"/>
                <TextInput id="asunto" v-model="form.asunto" type="text" autocomplete="asunto" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.asunto" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="descripcion" value="Descripción" :required="true"/>
                <Textarea id="descripcion" v-model="form.descripcion" autoResize rows="5" cols="30" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                <InputError :message="$page.props.errors.descripcion" class="mt-2" />
            </div>
            
            <!-- SOLO muestra estos 2 dropdowns si es un admin o editor Y si estamos en modo updating -->
            <div class="col-span-6 sm:col-span-6"
                v-if="
                updating && 
                ($page.props.user.roles.includes('admin') || $page.props.user.roles.includes('editor'))
                ">
                <!-- Dropdown RESPONSABLE -->
                <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="responsable_id" value="Responsable" :required="false"/>
                    <Dropdown
                        id="responsable_id"
                        v-model="form.responsable_id"
                        :options="usuariosResponsables"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Seleccione un responsable"
                        class="w-full mt-1 md:w-14rem border border-gray-300"
                    />
                    <InputError :message="$page.props.errors.responsable_id" class="mt-2" />
                </div>

                <!-- Dropdown ESTADOS -->
                <div class="col-span-6 sm:col-span-6 mt-3">
                    <InputLabel for="estado_ticket_id" value="Estado" :required="false"/>
                    <Dropdown
                        id="estado_ticket_id"
                        v-model="form.estado_ticket_id"
                        :options="estados"
                        optionLabel="estado"
                        optionValue="id"
                        placeholder="Seleccione un estado"
                        class="w-full mt-1 md:w-14rem border border-gray-300"
                        :disabled="disableEstadoDropdown"
                    />
                    <InputError :message="$page.props.errors.estado_ticket_id" class="mt-2" />
                </div>
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>