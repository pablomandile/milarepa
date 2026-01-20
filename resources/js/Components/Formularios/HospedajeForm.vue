<script>
    export default {
        name: 'HospedajesForm'
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

        lugaresHospedaje: {
            type: Array,
            default: () => []
        }

    });

    defineEmits(['submit']);

</script>

<template>
    <FormSection @submitted="$emit('submit')">
        <template #title>
            <SectionTitle>
                <template #title>
                    {{ updating ? 'Actualizar Hospedaje' : 'Nuevo Hospedaje' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando el Hospedaje seleccionada' : 'Agregando un nuevo Hospedaje.' }}
                </template>
            </SectionTitle>
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="nombre" value="Nombre" :required="true"/>
                <TextInput id="nombre" v-model="form.nombre" type="text" autocomplete="nombre" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.nombre" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="descripcion" value="Descripción" :required="false"/>
                <TextInput id="descripcion" v-model="form.descripcion" type="text" autocomplete="descripcion" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.descripcion" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="precio" value="Precio" :required="true"/>
                <TextInput id="precio" v-model="form.precio" type="number"  step="1" min="0" autocomplete="precio" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.precio" class="mt-2" />
            </div>
            <!-- Lugar de Hospedaje (Dropdown) -->
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="lugar_hospedaje_id" value="Lugar de Hospedaje" :required="true"/>
                <Dropdown
                    id="lugar_hospedaje_id"
                    v-model="form.lugar_hospedaje_id"
                    :options="lugaresHospedaje"
                    optionLabel="nombre"
                    optionValue="id"
                    placeholder="Seleccione un lugar"
                    class="w-full mt-1 md:w-14rem border border-gray-300"
                />
                <InputError :message="$page.props.errors.lugar_hospedaje_id" class="mt-2" />
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>