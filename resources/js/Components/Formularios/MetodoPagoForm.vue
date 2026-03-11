<script>
    export default {
        name: 'MétodosPagoForm'
    }
</script>

<script setup>
import FormSection from '@/Components/FormSection.vue'
import SectionTitle from '@/Components/SectionTitle.vue'
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';
import SingleImageUploader from '@/Components/SingleImageUploader.vue';

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
    imagenPreviewUrl: {
        type: String,
        default: ''
    }
})

defineEmits(['submit'])

</script>

<template>
    <FormSection @submitted="$emit('submit')">
        <template #title>
            <SectionTitle>
                <template #title>
                    {{ updating ? 'Actualizar Método de Pago' : 'Nuevo Método de Pago' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando el Método de Pago seleccionado' : 'Agregando un nuevo Método de Pago.' }}
                </template>
            </SectionTitle>
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="nombre" value="Nombre" :required="true"/>
                <TextInput id="nombre" v-model="form.nombre" type="text" autocomplete="nombre" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.nombre" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="descripcion" value="Descripción" :required="true"/>
                <TextInput id="descripcion" v-model="form.descripcion" type="text" autocomplete="descripcion" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.descripcion" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="tipo_de_pago" value="Tipo de pago" :required="true"/>
                <select
                    id="tipo_de_pago"
                    v-model="form.tipo_de_pago"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    <option value="" disabled>Seleccionar tipo</option>
                    <option value="Online">Online</option>
                    <option value="Presencial">Presencial</option>
                </select>
                <InputError :message="$page.props.errors.tipo_de_pago" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="imagen_id" value="Imagen" :required="false"/>
                <div class="flex items-start gap-4">
                    <SingleImageUploader
                        v-model:imagenId="form.imagen_id"
                        folder="img/mpago"
                    />
                    <div v-if="imagenPreviewUrl" class="flex items-center gap-2">
                        <img
                            :src="imagenPreviewUrl"
                            alt="Imagen actual"
                            class="h-16 w-16 rounded border border-gray-200 object-cover"
                        />
                        <span class="text-sm text-gray-500">Actual</span>
                    </div>
                </div>
                <InputError :message="$page.props.errors.imagen_id" class="mt-2" />
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
