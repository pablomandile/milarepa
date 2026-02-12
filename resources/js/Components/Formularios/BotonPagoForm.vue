<script>
    export default {
        name: 'BotonPagoForm'
    }
</script>

<script setup>
import FormSection from '@/Components/FormSection.vue'
import SectionTitle from '@/Components/SectionTitle.vue'
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import Dropdown from 'primevue/dropdown';

defineProps({
    form: {
        type: Object,
        required: true
    },
    metodosPago: {
        type: Array,
        default: () => []
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
                    {{ updating ? 'Actualizar Botón de Pago' : 'Crear Botón de Pago' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando el botón de pago seleccionado.' : 'Creando un nuevo botón de pago.' }}
                </template>
            </SectionTitle>
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="nombre" value="Nombre" :required="true"/>
                <TextInput id="nombre" v-model="form.nombre" type="text" autocomplete="nombre" class="mt-1 block w-full" maxlength="255" />
                <InputError :message="$page.props.errors.nombre" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-4">
                <InputLabel for="descripcion" value="Descripción" />
                <TextInput id="descripcion" v-model="form.descripcion" type="text" autocomplete="descripcion" class="mt-1 block w-full" maxlength="255" />
                <InputError :message="$page.props.errors.descripcion" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-4">
                <InputLabel for="link" value="Link" :required="true"/>
                <TextInput id="link" v-model="form.link" type="text" autocomplete="link" class="mt-1 block w-full" maxlength="255" />
                <InputError :message="$page.props.errors.link" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-4">
                <InputLabel for="metodo_pago_id" value="Método de Pago" :required="true"/>
                <Dropdown
                    id="metodo_pago_id"
                    v-model="form.metodo_pago_id"
                    :options="metodosPago"
                    optionLabel="nombre"
                    optionValue="id"
                    placeholder="Seleccione método de pago"
                    class="w-full mt-1 border border-gray-300"
                />
                <InputError :message="$page.props.errors.metodo_pago_id" class="mt-2" />
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
