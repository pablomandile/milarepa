<script>
export default {
    name: 'CicloForm'
}
</script>

<script setup>
import FormSection from '@/Components/FormSection.vue'
import SectionTitle from '@/Components/SectionTitle.vue'
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';

defineProps({
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

defineEmits(['submit']);
</script>

<template>
    <FormSection @submitted="$emit('submit')">
        <template #title>
            <SectionTitle>
                <template #title>
                    {{ updating ? 'Actualizar Ciclo' : 'Crear Ciclo' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando el ciclo seleccionado.' : 'Crea un nuevo ciclo para asociarlo a las clases.' }}
                </template>
            </SectionTitle>
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="nombre" value="Nombre" :required="true" />
                <TextInput id="nombre" v-model="form.nombre" type="text" autocomplete="off" class="mt-1 block w-full" />
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

