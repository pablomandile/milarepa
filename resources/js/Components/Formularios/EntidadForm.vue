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
import InputSwitch from 'primevue/inputswitch';
import InputMask from 'primevue/inputmask';

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
            {{ updating ? 'Actualizando la Entidad seleccionada' : 'Las Entidades pueden ser Centros, Anexos, lugares de retiro o salas para charlas' }}
        </template>
        <template #form>
            <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="nombre" class="text-indigo-400" v-tooltip="'Ingrese el nombre de la entidad'" value="Nombre" :required="true"/>
                    <TextInput id="nombre" v-tooltip="'Ingrese el nombre de la entidad'" v-model="form.nombre" type="text" autocomplete="nombre" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.nombre" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="descripcion" class="text-indigo-400" value="Descripción" :required="true"/>
                    <TextInput id="descripcion" v-model="form.descripcion" type="text" autocomplete="descripcion" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.descripcion" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="abreviacion" class="text-indigo-400" value="Abreviación" :required="true"/>
                    <TextInput id="abreviacion" v-model="form.abreviacion" type="text" autocomplete="abreviacion" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.abreviacion" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="direccion" class="text-indigo-400" value="Dirección" :required="true"/>
                    <TextInput id="direccion" v-model="form.direccion" type="text" autocomplete="direccion" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.direccion" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="telefono" class="text-indigo-400" value="Teléfono" :required="true"/>
                    <TextInput id="telefono" v-model="form.telefono" type="text" autocomplete="telefono" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.telefono" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="whatsapp" class="text-indigo-400" value="WhatsApp" :required="false"/>
                    <!-- <TextInput id="whatsapp" v-model="form.whatsapp" type="text" autocomplete="whatsapp" class="mt-1 block w-full" /> -->
                    <InputMask id="whatsapp" v-model="form.whatsapp" mask="+999 99 9999 9999" 
                    placeholder="+549 11 1234 5678" slotChar="_" :unmask="true"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"/>

                <InputError :message="$page.props.errors.whatsapp" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="web_uri" class="text-indigo-400" value="Web" :required="false"/>
                    <TextInput id="web_uri" v-model="form.web_uri" type="text" autocomplete="web_uri" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.web_uri" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="instagram_uri" class="text-indigo-400" value="Instagram" :required="false"/>
                    <TextInput id="instagram_uri" v-model="form.instagram_uri" type="text" autocomplete="instagram_uri" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.instagram_uri" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="facebook_uri" class="text-indigo-400" value="Facebook" :required="false"/>
                    <TextInput id="facebook_uri" v-model="form.facebook_uri" type="text" autocomplete="facebook_uri" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.facebook_uri" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="twitter_uri" class="text-indigo-400" value="Twitter" :required="false"/>
                    <TextInput id="twitter_uri" v-model="form.twitter_uri" type="text" autocomplete="twitter_uri" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.twitter_uri" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="youtube_uri" class="text-indigo-400" value="Youtube" :required="false"/>
                    <TextInput id="youtube_uri" v-model="form.youtube_uri" type="text" autocomplete="youtube_uri" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.youtube_uri" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="logo_uri" class="text-indigo-400" value="Logo" :required="false"/>
                    <TextInput id="logo_uri" v-model="form.logo_uri" type="text" autocomplete="logo_uri" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.logo_uri" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="email1" class="text-indigo-400" value="Correo electrónico" :required="false"/>
                    <TextInput id="email1" v-model="form.email1" type="text" autocomplete="email1" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.email1" class="mt-2" />
            </div>
            <div class="col-span-6 sm:col-span-6">
                    <InputLabel for="email2" class="text-indigo-400" value="Correo electrónico secundario" :required="false"/>
                    <TextInput id="email2" v-model="form.email2" type="text" autocomplete="email2" class="mt-1 block w-full" />
                <InputError :message="$page.props.errors.email2" class="mt-2" />
            </div>
            <div class="mt-4 flex items-center col-span-6">
                <InputSwitch
                    v-model="form.entidad_principal" 
                    class="mr-3"
                    />
                <label for="entidad_principal" class="block text-sm text-indigo-400">
                    ¿Entidad principal?
                </label>
                <InputError :message="$page.props.errors.entidad_principal" class="ml-2" />
            </div>
        </template>
        <template #actions>
            <PrimaryButton>
                {{ updating ? 'Actualizar' : 'Crear' }}
            </PrimaryButton>
        </template>
    </FormSection>
</template>
