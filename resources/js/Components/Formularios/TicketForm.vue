<script>
    export default {
        name: 'TicketsForm'
    }
</script>

<script setup>
import FormSection from '@/Components/FormSection.vue'
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import Textarea from 'primevue/textarea';



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
            {{ updating ? 'Actualizar Ticket' : 'Nuevo Ticket' }}
        </template>
        <template #description>
            {{ updating ? 'Actualizando el Ticket seleccionada' : 'Agregando un nuevo Ticket. Recibirás ayuda a la brevedad.' }}
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="asunto" value="Asunto" :required="true"/>
                <TextInput id="asunto" v-model="form.asunto" type="text" autocomplete="asunto" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.asunto" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="descripcion" value="Descripción" :required="true"/>
                <Textarea id="descripcion" v-model="form.descripcion" autoResize rows="5" cols="30" 
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
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