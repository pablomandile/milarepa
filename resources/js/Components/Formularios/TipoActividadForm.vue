<script>
    export default {
        name: 'TiposactividadForm'
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
    })

    defineEmits(['submit'])

</script>

<template>
    <FormSection @submitted="$emit('submit')">
        <template #title>
            <SectionTitle>
                <template #title>
                    {{ updating ? 'Actualizar Tipo de Actividad' : 'Crear Tipo de Actividad' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando el Tipo de Actividad seleccionada' : 'Creando un nuevo tipo de actividad para asignar a actividades.' }}
                </template>
            </SectionTitle>
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="abreviacion" value="Abreviación" :required="true"/>
                <TextInput id="abreviacion" v-model="form.abreviacion" type="text" autocomplete="abreviacion" class="mt-1 block w-full" maxlength="20" />
                <InputError :message="$page.props.errors.abreviacion" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-4">
                <InputLabel for="nombre" value="Nombre" />
                <TextInput id="nombre" v-model="form.nombre" type="text" autocomplete="nombre" class="mt-1 block w-full" maxlength="50" />
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