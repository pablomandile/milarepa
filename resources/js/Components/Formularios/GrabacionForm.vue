<script setup>
import FormSection from '@/Components/FormSection.vue';
import SectionTitle from '@/Components/SectionTitle.vue';
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

    updating: {
        type: Boolean,
        required: true,
        default: false
    },
    botonesPago: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['submit', 'update:form']);

</script>

<template>
    <FormSection @submitted="$emit('submit')">
        <template #title>
            <SectionTitle>
                <template #title>
                    {{ updating ? 'Actualizar Nombre de la Grabación' : 'Nueva Grabación' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando el nombre de la Grabación seleccionada' : 'Agregando una nueva Grabación.' }}
                </template>
            </SectionTitle>
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="nombre" value="Nombre" :required="true"/>
                <TextInput id="nombre" v-model="form.nombre" type="text" autocomplete="nombre" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.nombre" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-4">
                <InputLabel for="botonpago_id" value="Boton de Pago" :required="false"/>
                <Dropdown
                    id="botonpago_id"
                    v-model="form.botonpago_id"
                    :options="botonesPago"
                    optionLabel="nombre"
                    optionValue="id"
                    placeholder="Seleccione un boton de pago"
                    class="w-full mt-1 md:w-14rem border border-gray-300"
                    showClear
                />
                <InputError :message="$page.props.errors.botonpago_id" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-4">
                <InputLabel for="valor" value="Precio" :required="true"/>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">$</span>
                    <TextInput
                        id="valor"
                        v-model="form.valor"
                        type="number"
                        min="0"
                        step="0.01"
                        autocomplete="off"
                        class="pl-7 block w-full"
                    />
                </div>
                <InputError :message="$page.props.errors.valor" class="mt-2" />
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
