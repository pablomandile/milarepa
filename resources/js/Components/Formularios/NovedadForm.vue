<script>
    export default {
        name: 'NovedadesForm'
    }
</script>

<script setup>
import FormSection from '@/Components/FormSection.vue'
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import Calendar from 'primevue/calendar';

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
            {{ updating ? 'Actualizar Novedad' : 'Nueva Novedad' }}
        </template>
        <template #description>
            {{ updating ? 'Actualizando la Novedad seleccionada' : 'Agregando una nueva Novedad.' }}
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="nombre" value="Nombre" :required="true"/>
                <TextInput id="nombre" v-model="form.nombre" type="text" autocomplete="nombre" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.nombre" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="descripcion" value="Descripcion" :required="false"/>
                <textarea
                    id="descripcion"
                    v-model="form.descripcion"
                    rows="4"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 resize-y"
                ></textarea>
                <InputError :message="$page.props.errors.descripcion" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="fecha" value="Fecha" :required="false"/>
                <Calendar v-model="form.fecha" 
                id="fecha" 
                dateFormat="dd/mm/yy"
                class="w-3/6 mt-1"
                :showIcon="true"
                />
                <InputError :message="$page.props.errors.fecha" class="mt-2" />
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
