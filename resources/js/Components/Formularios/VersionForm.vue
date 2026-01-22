<script>
    export default {
        name: 'VersionForm'
    }
</script>

<script setup>
import FormSection from '@/Components/FormSection.vue'
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
})

defineEmits(['submit'])
</script>

<template>
    <FormSection @submitted="$emit('submit')">
        <template #title>
            {{ updating ? 'Actualizar Versión' : 'Nueva Versión' }}
        </template>
        <template #description>
            {{ updating ? 'Actualizando la versión seleccionada' : 'Agregando una nueva versión del sistema.' }}
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="version" value="Versión" :required="true"/>
                <TextInput 
                    id="version" 
                    v-model="form.version" 
                    type="text" 
                    placeholder="Ej: v1.0.0, 2024.1, etc."
                    autocomplete="off" 
                    class="mt-1 block w-full" 
                />
                <InputError :message="$page.props.errors.version" class="mt-2" />
                <p class="text-sm text-gray-500 mt-1">
                    Ingrese el número o código de la versión del sistema.
                </p>
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
