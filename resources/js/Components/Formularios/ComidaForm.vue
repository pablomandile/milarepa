<script>
    export default {
        name: 'ComidasForm'
    }
</script>

<script setup>
import FormSection from '@/Components/FormSection.vue'
import SectionTitle from '@/Components/SectionTitle.vue'
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import InputSwitch from 'primevue/inputswitch';
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
                    {{ updating ? 'Actualizar Comida' : 'Nueva Comida' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando la Comida seleccionada' : 'Agregando una nueva Comida.' }}
                </template>
            </SectionTitle>
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6 mb-2">
                <InputLabel for="nombre" value="Nombre" :required="true"/>
                <TextInput id="nombre" v-model="form.nombre" type="text" autocomplete="nombre" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.nombre" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mb-2">
                <InputLabel for="descripcion" value="Descripción" :required="true"/>
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
                <TextInput id="precio" v-model="form.precio" type="number"  step="50" min="0" autocomplete="precio" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.precio" class="mt-2" />
            </div>
            <div class="mt-4 flex items-center col-span-6 mb-2">
                <InputSwitch
                    v-model="form.vegano" 
                    class="mr-3"
                    />
                <label for="vegano" class="block text-sm text-indigo-400">
                    ¿Apto vegano?
                </label>
                <InputError :message="$page.props.errors.vegano" class="ml-2" />
            </div>
            <div class="mt-4 flex items-center col-span-6 mb-2">
                <InputSwitch
                    v-model="form.celiaco" 
                    class="mr-3"
                    />
                <label for="celiaco" class="block text-sm text-indigo-400">
                    ¿Apto celíaco?
                </label>
                <InputError :message="$page.props.errors.celiaco" class="ml-2" />
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
