<script>
    export default {
        name: 'MonedasForm'
    }
</script>

<script setup>
import FormSection from '@/Components/FormSection.vue'
import SectionTitle from '@/Components/SectionTitle.vue'
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import Checkbox from 'primevue/checkbox';

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
                    {{ updating ? 'Actualizar Moneda' : 'Nueva Moneda' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando la Moneda seleccionada' : 'Agregando una nueva Moneda/a.' }}
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
                <InputLabel for="simbolo" value="Símbolo" :required="true"/>
                <TextInput id="simbolo" v-model="form.simbolo" type="text" autocomplete="simbolo" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.simbolo" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <div class="flex items-center gap-2">
                    <Checkbox id="es_principal" v-model="form.es_principal" :binary="true" />
                    <InputLabel for="es_principal" value="Moneda principal" />
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Los precios simples de servicios y todo lo histórico se interpretan en la moneda principal.
                    Marcar esta desmarca la anterior; siempre debe existir una.
                </p>
                <InputError :message="$page.props.errors.es_principal" class="mt-2" />
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>