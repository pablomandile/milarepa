<script setup>
import FormSection from '@/Components/FormSection.vue';
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true
    },

    updating: {
        type: Boolean,
        required: true,
        default: false
    }
});

const emit = defineEmits(['submit', 'update:form']);

</script>

<template>
    <FormSection @submitted="$emit('submit')">
        <template #title>
            {{ updating ? 'Actualizar Nombre de la Grabación' : 'Nueva Grabación' }}
        </template>
        <template #description>
            {{ updating ? 'Actualizando el nombre de la Grabación seleccionada' : 'Agregando una nueva Grabación.' }}
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="nombre" value="Nombre" :required="true"/>
                <TextInput id="nombre" v-model="form.nombre" type="text" autocomplete="nombre" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.nombre" class="mt-2" />
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
