<script>
    export default {
        name: 'MetodosPagoForm'
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
            {{ updating ? 'Actualizar Método de Pago' : 'Nuevo Método de Pago' }}
        </template>
        <template #description>
            {{ updating ? 'Actualizando el Método de Pago seleccionado' : 'Agregando un nuevo Método de Pago.' }}
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="nombre" value="Nombre" :required="true"/>
                <TextInput id="nombre" v-model="form.nombre" type="text" autocomplete="nombre" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.nombre" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="descripcion" value="Descripción" :required="true"/>
                <TextInput id="descripcion" v-model="form.descripcion" type="text" autocomplete="descripcion" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.descripcion" class="mt-2" />
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>