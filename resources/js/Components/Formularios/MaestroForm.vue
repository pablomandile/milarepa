<script>
    export default {
        name: 'MaestrosForm'
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
                    {{ updating ? 'Actualizar Maestro' : 'Nuevo Maestro' }}
                </template>
                <template #description>
                    {{ updating ? 'Actualizando el Maestro seleccionado' : 'Agregando un nuevo Maestro/a para estar a cargo de actividades.' }}
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
                <InputLabel for="email" value="Correo electrónico" :required="true"/>
                <TextInput id="email" v-model="form.email" type="text" autocomplete="email" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.email" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="telefono" value="Telefono" :required="false"/>
                <TextInput id="telefono" v-model="form.telefono" type="text" autocomplete="telefono" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.telefono" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel
                    for="imagen_id"
                    class="text-indigo-400 mb-2"
                    value="Foto del Maestro"
                    :required="false"
                />
                <div class="flex items-start gap-4">
                    <SingleImageUploader
                        v-model:imagenId="form.imagen_id"
                        folder="img/maestros"
                    />
                    <div v-if="imagenPreviewUrl" class="flex items-center gap-2">
                        <img
                            :src="imagenPreviewUrl"
                            alt="Imagen actual"
                            class="h-16 w-16 rounded border border-gray-200 object-cover"
                        />
                        <span class="text-xs text-gray-500">Actual</span>
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
