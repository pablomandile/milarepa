<script setup>
import FormSection from '@/Components/FormSection.vue';
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import Dropdown from 'primevue/dropdown';
import { defineProps, defineEmits } from 'vue';

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
  }
});

const emit = defineEmits(['submit']);

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
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="membresia_id" value="Membresía" :required="true"/>
                <Dropdown
                    id="membresia_id"
                    v-model="form.membresia_id"
                    :options="membresias"
                    optionLabel="label"
                    optionValue="id"
                    placeholder="Membresía..."
                    class="w-full mt-1 border border-gray-300"
                />
                <InputError :message="$page.props.errors.membresia_id" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="moneda_id" value="Moneda" :required="true"/>
                <Dropdown
                    id="moneda_id"
                    v-model="form.moneda_id"
                    :options="monedas"
                    optionLabel="nombre"
                    optionValue="id"
                    placeholder="Moneda..."
                    class="w-full mt-1 border border-gray-300"
                />
                <InputError :message="$page.props.errors.moneda_id" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="precio" value="Precio" :required="true"/>
                <TextInput
                    id="precio"
                    v-model="form.precio"
                    type="number"
                    step="1"
                    min="0"
                    class="mt-2 block w-full"
                />
                <InputError :message="$page.props.errors.precio" class="mt-2" />
            </div>
    </template>

    <template #actions>
      <PrimaryButton>
        Agregar
      </PrimaryButton>
    </template>
  </FormSection>
</template>
