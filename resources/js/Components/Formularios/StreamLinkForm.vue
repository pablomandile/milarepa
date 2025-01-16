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
            Añadir Link al Stream
      </div>
    </template>

    <template #description>
        <div class="max-w-sm">
            Agrega un nuevo link de streaming
      </div>
    </template>

    <template #form>
            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="nombre" value="Nombre" :required="false"/>
                <TextInput
                    id="nombre"
                    v-model="form.nombre"
                    type="text"
                    class="mt-2 block w-full"
                />
                <InputError :message="$page.props.errors.nombre" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="link" value="Link" :required="true"/>
                <TextInput
                    id="link"
                    v-model="form.link"
                    type="text"
                    class="mt-2 block w-full"
                />
                <InputError :message="$page.props.errors.link" class="mt-2" />
            </div>
    </template>

    <template #actions>
      <PrimaryButton>
        Agregar
      </PrimaryButton>
    </template>
  </FormSection>
</template>
