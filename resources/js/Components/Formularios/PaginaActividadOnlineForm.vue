<script>
export default {
    name: 'PaginaActividadOnlineForm'
}
</script>

<script setup>
import FormSection from '@/Components/FormSection.vue';
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
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
});

defineEmits(['submit']);
</script>

<template>
    <FormSection @submitted="$emit('submit')">
        <template #title>
            {{ updating ? 'Actualizar Conf. Pagina Actividades Online' : 'Nueva Conf. Pagina Actividades Online' }}
        </template>
        <template #description>
            {{ updating ? 'Edita la configuracion de la pagina para el mes seleccionado.' : 'Configura la pagina de actividades online por mes.' }}
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="mes_referencia" value="Mes" :required="true" />
                <input
                    id="mes_referencia"
                    v-model="form.mes_referencia"
                    type="month"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <InputError :message="$page.props.errors.mes_referencia" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-6 mt-3">
                <InputLabel for="imagen_id" value="Imagen encabezado" />
                <div class="mt-2 flex items-start gap-4">
                    <SingleImageUploader
                        v-model:imagenId="form.imagen_id"
                        folder="img/pages"
                    />
                    <div v-if="imagenPreviewUrl" class="flex items-center gap-2">
                        <img
                            :src="imagenPreviewUrl"
                            alt="Imagen encabezado actual"
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

