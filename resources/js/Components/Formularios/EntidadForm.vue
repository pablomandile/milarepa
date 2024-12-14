<script>
    export default {
        name: 'EntidadesForm'
    }
</script>

<script setup>
import FormSection from '@/Components/FormSection.vue'
import InputError from '../InputError.vue';
import InputLabel from '../InputLabel.vue';
import PrimaryButton from '../PrimaryButton.vue';
import TextInput from '../TextInput.vue';

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
            {{ updating ? 'Actualizar Entidad' : 'Nueva Entidad' }}
        </template>
        <template #description>
            {{ updating ? 'Actualizando la Entidad seleccionado' : 'Agregando una nueva Entidad para estar a cargo de actividades.' }}
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="nombre" value="Nombre" :required="true"/>
                <TextInput id="nombre" v-model="form.nombre" type="text" autocomplete="nombre" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.nombre" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="descripcion" value="Descripción" :required="true"/>
                <TextInput id="descripcion" v-model="form.descripcion" type="text" autocomplete="descripcion" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.descripcion" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="direccion" value="Dirección" :required="true"/>
                <TextInput id="direccion" v-model="form.direccion" type="text" autocomplete="direccion" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.direccion" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="telefono" value="Teléfono" :required="true"/>
                <TextInput id="telefono" v-model="form.telefono" type="text" autocomplete="telefono" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.telefono" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="whatsapp" value="WhatsApp" :required="false"/>
                <TextInput id="whatsapp" v-model="form.whatsapp" type="text" autocomplete="whatsapp" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.whatsapp" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="web_uri" value="Web" :required="false"/>
                <TextInput id="web_uri" v-model="form.web_uri" type="text" autocomplete="web_uri" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.web_uri" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="instagram_uri" value="Instagram" :required="false"/>
                <TextInput id="instagram_uri" v-model="form.instagram_uri" type="text" autocomplete="instagram_uri" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.instagram_uri" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="facebook_uri" value="Facebook" :required="false"/>
                <TextInput id="facebook_uri" v-model="form.facebook_uri" type="text" autocomplete="facebook_uri" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.facebook_uri" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="youtube_uri" value="Youtube" :required="false"/>
                <TextInput id="youtube_uri" v-model="form.youtube_uri" type="text" autocomplete="youtube_uri" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.youtube_uri" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="logo_uri" value="Logo" :required="false"/>
                <TextInput id="logo_uri" v-model="form.logo_uri" type="text" autocomplete="logo_uri" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.logo_uri" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="email1" value="Correo electrónico" :required="false"/>
                <TextInput id="email1" v-model="form.email1" type="text" autocomplete="email1" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.email1" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                <InputLabel for="email2" value="Correo electrónico secundario" :required="false"/>
                <TextInput id="email2" v-model="form.email2" type="text" autocomplete="email2" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.email2" class="mt-2" />
            </div>
            <!-- Checkbox para booleano -->
            <div class="mt-4 flex items-center">
                <input
                    id="entidad_principal"
                    v-model="form.entidad_principal"
                    type="checkbox"
                    class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                />
                <label for="entidad_principal" class="ml-3 block text-sm text-gray-900">Entidad principal?</label>
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>