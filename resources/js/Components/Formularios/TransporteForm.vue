<script>
    export default {
        name: 'TransporteForm'
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
        updating: {
        type: Boolean,
        required: true,
        default: false
    },
        botonesPago: {
        type: Array,
        default: () => []
    }
    })

    defineEmits(['submit'])

</script>

<template>
    <FormSection @submitted="$emit('submit')">
        <template #title>
            <SectionTitle>
                <template #title>
                    {{ updating ? 'Actualizar Transporte' : 'Nuevo Transporte' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando el Transporte seleccionado' : 'Agregando un nuevo Transporte.' }}
                </template>
            </SectionTitle>
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6 mb-2">
                <InputLabel for="descripcion" value="DescripciÃ³n" :required="false"/>
                <TextInput id="descripcion" v-model="form.descripcion" type="text" autocomplete="descripcion" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.descripcion" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mb-2">
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
            <div class="col-span-6 sm:col-span-6 mb-2">
                <InputLabel for="precio" value="Precio" :required="true"/>
                <TextInput id="precio" v-model="form.precio" type="text" autocomplete="precio" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.precio" class="mt-2" />
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>


